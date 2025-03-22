<?php

namespace Mak8Tech\ZraSmartInvoice\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ZraRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $limiterName
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $limiterName = 'zra', int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $key = $this->getLimiterKey($request, $limiterName);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $maxAttempts);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            RateLimiter::remaining($key, $maxAttempts)
        );
    }

    /**
     * Get the rate limiter key for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $limiterName
     * @return string
     */
    protected function getLimiterKey(Request $request, string $limiterName): string
    {
        return $limiterName . ':' . (
            $request->user()
                ? $request->user()->getAuthIdentifier()
                : $request->ip()
        );
    }

    /**
     * Create a 'too many attempts' response.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildTooManyAttemptsResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        $headers = [
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ];

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again in ' . ceil($retryAfter / 60) . ' minute(s).',
        ], Response::HTTP_TOO_MANY_REQUESTS, $headers);
    }

    /**
     * Add the rate limit headers to the response.
     *
     * @param  \Illuminate\Http\Response  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @return \Illuminate\Http\Response
     */
    protected function addRateLimitHeaders($response, int $maxAttempts, int $remainingAttempts)
    {
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);
    }
}
