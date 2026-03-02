<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DeferredLink;
use App\Models\Link;
use App\Models\LinkClick;
use App\Services\DeviceDetectionService;
use App\Services\FingerprintService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RedirectController extends Controller
{
    public function __construct(
        private DeviceDetectionService $deviceDetection,
        private FingerprintService $fingerprintService
    ) {}

    public function handle(Request $request, string $shortCode): View|\Illuminate\Http\Response
    {
        $link = Link::where('short_code', $shortCode)->first();

        if (!$link || $link->isExpired()) {
            abort(404);
        }

        $ip = $request->ip() ?? '';
        $userAgent = $request->userAgent() ?? '';
        $language = $request->header('Accept-Language', '');
        $platform = $this->deviceDetection->detectPlatform($userAgent);
        $referer = $request->header('Referer');

        // Compute initial fingerprint (without screen dims — beacon will update)
        $fingerprint = $this->fingerprintService->compute($ip, $userAgent, $language);

        // Record click
        LinkClick::create([
            'link_id' => $link->id,
            'app_id' => $link->app_id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'platform' => $platform,
            'referer' => $referer,
        ]);

        // Insert deferred link row
        DeferredLink::create([
            'link_id' => $link->id,
            'app_id' => $link->app_id,
            'fingerprint' => $fingerprint,
            'platform' => $platform,
            'expires_at' => now()->addHours(config('deferred_linking.deferred_ttl_hours', 24)),
        ]);

        $app = $link->app;

        return view('redirect.index', [
            'link' => $link,
            'app' => $app,
            'platform' => $platform,
            'fingerprint' => $fingerprint,
            'deepLinkUri' => $link->deep_link_uri,
            'appStoreUrl' => $app->app_store_url,
            'playStoreUrl' => $app->play_store_url,
            'fallbackUrl' => $link->fallback_url,
        ]);
    }
}
