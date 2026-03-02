<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdminKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminKey = $request->header('X-Admin-Key');
        $expectedKey = config('deferred_linking.admin_key');

        if (!$adminKey || !$expectedKey || !hash_equals($expectedKey, $adminKey)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
