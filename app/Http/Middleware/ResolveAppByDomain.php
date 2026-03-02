<?php

namespace App\Http\Middleware;

use App\Models\App;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAppByDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $app = App::where('custom_domain', $host)->first();

        if ($app) {
            $request->attributes->set('app', $app);
        }

        return $next($request);
    }
}
