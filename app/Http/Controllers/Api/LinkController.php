<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLinkRequest;
use App\Models\App;
use App\Models\Link;
use App\Services\ShortCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function __construct(private ShortCodeService $shortCodeService) {}

    public function index(Request $request): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');

        $links = Link::where('app_id', $app->id)
            ->latest()
            ->paginate(20);

        return response()->json($links);
    }

    public function store(CreateLinkRequest $request): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');

        $shortCode = $this->shortCodeService->generate();

        $domain = $app->custom_domain ?? config('deferred_linking.default_domain');
        $protocol = app()->environment('production') ? 'https' : 'http';
        $shortUrl = "{$protocol}://{$domain}/{$shortCode}";

        $link = Link::create(array_merge($request->validated(), [
            'app_id' => $app->id,
            'short_code' => $shortCode,
        ]));

        return response()->json([
            'link' => $link,
            'short_url' => $shortUrl,
        ], 201);
    }

    public function show(Request $request, string $code): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');

        $link = Link::where('app_id', $app->id)
            ->where('short_code', $code)
            ->firstOrFail();

        $domain = $app->custom_domain ?? config('deferred_linking.default_domain');
        $protocol = app()->environment('production') ? 'https' : 'http';
        $shortUrl = "{$protocol}://{$domain}/{$link->short_code}";

        return response()->json([
            'link' => $link,
            'short_url' => $shortUrl,
        ]);
    }

    public function destroy(Request $request, string $code): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');

        $link = Link::where('app_id', $app->id)
            ->where('short_code', $code)
            ->firstOrFail();

        $link->delete();

        return response()->json(['message' => 'Link deleted']);
    }
}
