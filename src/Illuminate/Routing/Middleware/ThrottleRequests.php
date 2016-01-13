<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Cache\RateLimiter;

class ThrottleRequests
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new request throttler.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return mixed
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            return new Response('Too Many Attempts.', 429, [
                'Retry-After' => $this->limiter->availableIn($key),
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $response = $next($request);

        $remaining = $maxAttempts - $this->limiter->attempts($key);

        if (! $this->shouldBypassLimiterHit($response)) {
            $remaining -= $this->limiter->hit($key, $decayMinutes);
        }

        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
        ]);

        return $response;
    }

    /**
     * Resolve request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature($request)
    {
        return $request->fingerprint();
    }

    /**
     * Indicates if a not-modified response should be bypassed.
     *
     * @param  mixed  $response
     * @return bool
     */
    protected function shouldBypassLimiterHit($response)
    {
        return $response instanceof Response && $response->getStatusCode() === 304;
    }
}
