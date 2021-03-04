<?php

namespace Illuminate\Queue\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Container\Container;
use Throwable;

class CircuitBreaker
{
    /**
     * The maximum number of attempts allowed before the circuit is opened.
     *
     * @var int
     */
    protected $maxAttempts;

    /**
     * The number of minutes until the maximum attempts are reset.
     *
     * @var int
     */
    protected $decayMinutes;

    /**
     * The number of minutes to wait before retrying the job after an exception.
     *
     * @var int
     */
    protected $retryAfterMinutes;

    /**
     * The rate limiter key.
     *
     * @var string
     */
    protected $key;

    /**
     * The prefix of the rate limiter key.
     *
     * @var string
     */
    protected $prefix = 'circuit_breaker:';

    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new middleware instance.
     *
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @param  int  $retryAfterMinutes
     * @param  string  $key
     */
    public function __construct($maxAttempts = 10, $decayMinutes = 10, $retryAfterMinutes = 0, string $key = '')
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->retryAfterMinutes = $retryAfterMinutes;
        $this->key = $key;
    }

    /**
     * Process the job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {
        $this->limiter = Container::getInstance()->make(RateLimiter::class);

        if ($this->limiter->tooManyAttempts($jobKey = $this->getKey($job), $this->maxAttempts)) {
            return $job->release($this->getTimeUntilNextRetry($jobKey));
        }

        try {
            $next($job);

            $this->limiter->clear($jobKey);
        } catch (Throwable $throwable) {
            $this->limiter->hit($jobKey, $this->decayMinutes * 60);

            return $job->release($this->retryAfterMinutes * 60);
        }
    }

    /**
     * Set the prefix of the rate limiter key.
     *
     * @param  string  $prefix
     * @return $this
     */
    public function withPrefix(string $prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get the number of seconds that should elapse before the job is retried.
     *
     * @param  string  $key
     * @return int
     */
    protected function getTimeUntilNextRetry($key)
    {
        return $this->limiter->availableIn($key) + 3;
    }

    /**
     * Get the cache key associated for the rate limiter.
     *
     * @param  mixed  $job
     * @return string
     */
    protected function getKey($job)
    {
        return md5($this->prefix.(empty($this->key) ? get_class($job) : $this->key));
    }
}
