<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Redis\Factory as Redis;
use Illuminate\Redis\Limiters\DurationLimiter;
use Illuminate\Redis\Limiters\SlidingWindowDurationLimiter;

class ThrottleRequestsWithRedis extends ThrottleRequests
{
    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * The timestamp of the end of the current duration by key.
     *
     * @var array
     */
    public $decaysAt = [];

    /**
     * The number of remaining slots by key.
     *
     * @var array
     */
    public $remaining = [];

    /**
     * Create a new request throttler.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     */
    public function __construct(RateLimiter $limiter, Redis $redis)
    {
        parent::__construct($limiter);

        $this->redis = $redis;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  array  $limits
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    protected function handleRequest($request, Closure $next, array $limits)
    {
        foreach ($limits as $limit) {
            if ($this->tooManyAttempts($limit->key, $limit->maxAttempts, $limit->decaySeconds, $limit->slidingWindow)) {
                throw $this->buildException($request, $limit->key, $limit->maxAttempts, $limit->responseCallback, $limit->decaySeconds, $limit->slidingWindow);
            }

            if (! $limit->afterCallback) {
                $this->hit($limit->key, $limit->maxAttempts, $limit->decaySeconds, $limit->slidingWindow);
            }
        }

        $response = $next($request);

        foreach ($limits as $limit) {
            if ($limit->afterCallback && ($limit->afterCallback)($response)) {
                $this->hit($limit->key, $limit->maxAttempts, $limit->decaySeconds, $limit->slidingWindow);
            }

            $response = $this->addHeaders(
                $response,
                $limit->maxAttempts,
                $this->calculateRemainingAttempts($limit->key, $limit->maxAttempts)
            );
        }

        return $response;
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int  $decaySeconds
     * @param  bool  $slidingWindow
     * @return bool
     */
    protected function tooManyAttempts($key, $maxAttempts, $decaySeconds, $slidingWindow = false)
    {
        $limiter = $this->createLimiter($key, $maxAttempts, $decaySeconds, $slidingWindow);

        return tap($limiter->tooManyAttempts(), function () use ($key, $limiter) {
            [$this->decaysAt[$key], $this->remaining[$key]] = [
                $limiter->decaysAt, $limiter->remaining,
            ];
        });
    }

    /**
     * Increment the counter for the given key.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int  $decaySeconds
     * @param  bool  $slidingWindow
     * @return void
     */
    protected function hit($key, $maxAttempts, $decaySeconds, $slidingWindow = false)
    {
        $limiter = $this->createLimiter($key, $maxAttempts, $decaySeconds, $slidingWindow);

        $limiter->acquire();

        [$this->decaysAt[$key], $this->remaining[$key]] = [
            $limiter->decaysAt, $limiter->remaining,
        ];
    }

    /**
     * Create a new limiter instance.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int  $decaySeconds
     * @param  bool  $slidingWindow
     * @return \Illuminate\Redis\Limiters\DurationLimiter|\Illuminate\Redis\Limiters\SlidingWindowDurationLimiter
     */
    protected function createLimiter($key, $maxAttempts, $decaySeconds, $slidingWindow = false)
    {
        return $slidingWindow
            ? new SlidingWindowDurationLimiter($this->getRedisConnection(), $key, $maxAttempts, $decaySeconds)
            : new DurationLimiter($this->getRedisConnection(), $key, $maxAttempts, $decaySeconds);
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int|null  $retryAfter
     * @param  int  $decaySeconds
     * @param  bool  $slidingWindow
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null, $decaySeconds = 60, $slidingWindow = false)
    {
        return is_null($retryAfter) ? $this->remaining[$key] : 0;
    }

    /**
     * Get the number of seconds until the lock is released.
     *
     * @param  string  $key
     * @param  int  $decaySeconds
     * @param  bool  $slidingWindow
     * @return int
     */
    protected function getTimeUntilNextRetry($key, $decaySeconds = 60, $slidingWindow = false)
    {
        return $this->decaysAt[$key] - $this->currentTime();
    }

    /**
     * Get the Redis connection that should be used for throttling.
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function getRedisConnection()
    {
        return $this->redis->connection();
    }
}
