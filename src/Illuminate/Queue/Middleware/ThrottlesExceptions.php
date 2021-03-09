<?php

namespace Illuminate\Queue\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Container\Container;
use Throwable;

class ThrottlesExceptions
{
    /**
     * The developer specified key that the rate limiter should use.
     *
     * @var string
     */
    protected $key;

    /**
     * Indicates whether the throttle key should use the job's UUID.
     *
     * @var bool
     */
    protected $byJob = false;

    /**
     * The maximum number of attempts allowed before rate limiting applies.
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
    protected $retryAfterMinutes = 0;

    /**
     * The callback that determines if rate limiting should apply.
     *
     * @var callable
     */
    protected $whenCallback;

    /**
     * The prefix of the rate limiter key.
     *
     * @var string
     */
    protected $prefix = 'laravel_throttles_exceptions:';

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
     * @param  string  $key
     * @return void
     */
    public function __construct($maxAttempts = 10, $decayMinutes = 10)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
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
            if ($this->whenCallback && ! call_user_func($this->whenCallback, $throwable)) {
                throw $throwable;
            }

            $this->limiter->hit($jobKey, $this->decayMinutes * 60);

            return $job->release($this->retryAfterMinutes * 60);
        }
    }

    /**
     * Specify a callback that should determine if rate limiting behavior should apply.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function when(callable $callback)
    {
        $this->whenCallback = $callback;

        return $this;
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
     * Specify the number of seconds a job should be delayed when it is released (before it has reached its max exceptions).
     *
     * @param  int  $backoff
     * @return $this
     */
    public function backoff($backoff)
    {
        $this->retryAfterMinutes = $backoff;

        return $this;
    }

    /**
     * Get the cache key associated for the rate limiter.
     *
     * @param  mixed  $job
     * @return string
     */
    protected function getKey($job)
    {
        if ($this->key) {
            return $this->prefix.$this->key;
        } elseif ($this->byJob) {
            return $this->prefix.$job->job->uuid();
        }

        return $this->prefix.md5(get_class($job));
    }

    /**
     * Set the value that the rate limiter should be keyed by.
     *
     * @param  string  $key
     * @return $this
     */
    public function by($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Indicate that the throttle key should use the job's UUID.
     *
     * @return $this
     */
    public function byJob()
    {
        $this->byJob = true;

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
}
