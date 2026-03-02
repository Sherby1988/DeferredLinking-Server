<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveDeferredRequest;
use App\Models\App;
use App\Models\DeferredLink;
use App\Services\FingerprintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeferredLinkController extends Controller
{
    public function __construct(private FingerprintService $fingerprintService) {}

    public function resolve(ResolveDeferredRequest $request): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');

        $ip = $request->ip() ?? '';
        $userAgent = $request->input('user_agent');
        $platform = $request->input('platform');
        $language = $request->input('language', '');
        $screenWidth = (int) $request->input('screen_width', 0);
        $screenHeight = (int) $request->input('screen_height', 0);

        // Pass 1: exact fingerprint match
        $exactFingerprint = $this->fingerprintService->compute(
            $ip,
            $userAgent,
            $language,
            $screenWidth,
            $screenHeight
        );

        $deferred = DeferredLink::with('link')
            ->where('app_id', $app->id)
            ->where('fingerprint', $exactFingerprint)
            ->where('platform', $platform)
            ->where('resolved', false)
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->first();

        // Pass 2: loose fingerprint (ip+ua only) within last hour
        if (!$deferred) {
            $looseFingerprint = $this->fingerprintService->computeLoose($ip, $userAgent);

            $deferred = DeferredLink::with('link')
                ->where('app_id', $app->id)
                ->where('fingerprint', $looseFingerprint)
                ->where('platform', $platform)
                ->where('resolved', false)
                ->where('expires_at', '>', now())
                ->where('created_at', '>=', now()->subHour())
                ->orderByDesc('created_at')
                ->first();
        }

        if (!$deferred) {
            return response()->json(['matched' => false]);
        }

        $deferred->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);

        return response()->json([
            'matched' => true,
            'deep_link_uri' => $deferred->link->deep_link_uri,
            'short_code' => $deferred->link->short_code,
            'link_id' => $deferred->link_id,
        ]);
    }

    public function fingerprintUpdate(Request $request): \Illuminate\Http\Response
    {
        $data = $request->json()->all();

        $oldFp = $data['fp'] ?? null;
        $width = (int) ($data['w'] ?? 0);
        $height = (int) ($data['h'] ?? 0);
        $lang = $data['lang'] ?? '';

        if (!$oldFp) {
            return response('', 204);
        }

        // Find the most recent unresolved deferred link with the old fingerprint
        $deferred = DeferredLink::where('fingerprint', $oldFp)
            ->where('resolved', false)
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->first();

        if ($deferred) {
            // Recompute fingerprint from stored data + screen dims
            // We need IP+UA from original request; use the loose fp approach
            // The beacon updates the fingerprint to include screen dims for better matching
            $ip = $request->ip() ?? '';
            $ua = $request->userAgent() ?? '';

            $newFp = $this->fingerprintService->compute($ip, $ua, $lang, $width, $height);
            $deferred->update(['fingerprint' => $newFp]);
        }

        return response('', 204);
    }
}
