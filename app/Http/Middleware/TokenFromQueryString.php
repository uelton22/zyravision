<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts a ?token= query parameter into a Bearer Authorization header.
 *
 * HLS players (like media_kit/MPV) fetch the M3U8 playlist with proper
 * auth headers, but when fetching individual .ts segment URLs from the
 * playlist, they do NOT forward those headers. This middleware allows
 * the token to be passed as a query parameter instead.
 */
class TokenFromQueryString
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken() && $request->has('token')) {
            $request->headers->set('Authorization', 'Bearer ' . $request->query('token'));
        }

        return $next($request);
    }
}
