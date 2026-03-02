<?php

namespace App\Http\Middleware;

use App\Models\App;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');

        if (!$apiKey) {
            return response()->json(['error' => 'Missing X-Api-Key header'], 401);
        }

        $app = App::where('api_key', $apiKey)->first();

        if (!$app) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        $request->merge(['_app' => $app]);
        $request->attributes->set('app', $app);

        return $next($request);
    }
}
