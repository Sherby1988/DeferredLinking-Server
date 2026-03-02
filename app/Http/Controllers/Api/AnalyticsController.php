<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\Link;
use App\Models\LinkClick;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function linkStats(Request $request, string $code): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');

        $link = Link::where('app_id', $app->id)
            ->where('short_code', $code)
            ->firstOrFail();

        $total = LinkClick::where('link_id', $link->id)->count();

        $byPlatform = LinkClick::where('link_id', $link->id)
            ->selectRaw('platform, COUNT(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform');

        $byDay = LinkClick::where('link_id', $link->id)
            ->selectRaw("strftime('%Y-%m-%d', clicked_at) as date, COUNT(*) as count")
            ->groupByRaw("strftime('%Y-%m-%d', clicked_at)")
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return response()->json([
            'link' => $link,
            'total_clicks' => $total,
            'by_platform' => $byPlatform,
            'clicks_by_day' => $byDay,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        /** @var App $app */
        $app = $request->attributes->get('app');

        $totalClicks = LinkClick::where('app_id', $app->id)->count();
        $totalLinks = Link::where('app_id', $app->id)->count();

        $byPlatform = LinkClick::where('app_id', $app->id)
            ->selectRaw('platform, COUNT(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform');

        $topLinks = Link::where('app_id', $app->id)
            ->withCount('clicks')
            ->orderByDesc('clicks_count')
            ->limit(10)
            ->get(['id', 'short_code', 'deep_link_uri', 'created_at']);

        $clicksLast7Days = LinkClick::where('app_id', $app->id)
            ->where('clicked_at', '>=', now()->subDays(7))
            ->selectRaw("strftime('%Y-%m-%d', clicked_at) as date, COUNT(*) as count")
            ->groupByRaw("strftime('%Y-%m-%d', clicked_at)")
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'total_links' => $totalLinks,
            'total_clicks' => $totalClicks,
            'by_platform' => $byPlatform,
            'top_links' => $topLinks,
            'clicks_last_7_days' => $clicksLast7Days,
        ]);
    }
}
