<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiter
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveKey($request);
        $limit = $this->resolveLimit($request);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $retryAfter,
                ],
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->getTimestamp(),
                'Retry-After' => $retryAfter,
            ]);
        }

        RateLimiter::hit($key, 60);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => max(0, $limit - RateLimiter::attempts($key)),
            'X-RateLimit-Reset' => now()->addMinute()->getTimestamp(),
        ]);
    }

    protected function resolveKey(Request $request): string
    {
        $userId = $request->user()?->id ?? $request->ip();

        return 'api_rate:' . $userId;
    }

    protected function resolveLimit(Request $request): int
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        // Mobile apps (Flutter/Dart)
        if (str_contains($userAgent, 'dart') || str_contains($userAgent, 'zyravision-mobile')) {
            return (int) env('API_RATE_LIMIT_MOBILE', 60);
        }

        // Background/sync requests
        if ($request->hasHeader('X-Background-Sync')) {
            return (int) env('API_RATE_LIMIT_BACKGROUND', 10);
        }

        // Web/default
        return (int) env('API_RATE_LIMIT_WEB', 120);
    }
}
