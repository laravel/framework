<?php

namespace Illuminate\Queue\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Support\Arr;

class RateLimitsJobs
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * The name of the rate limiter.
     *
     * @var string
     */
    protected $limiterName;

    /**
     * Create a new rate limiter middleware instance.
     *
     * @param  string  $limiterName
     *
     * @return void
     */
    public function __construct($limiterName)
    {
        $this->limiter = app(RateLimiter::class);
        $this->limiterName = $limiterName;
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
        if (! is_null($limiter = $this->limiter->limiter($this->limiterName))) {
            $limiterResponse = call_user_func($limiter, $job);

            if ($limiterResponse instanceof Unlimited) {
                return $next($job);
            }

            return $this->handleJob(
                $job,
                $next,
                collect(Arr::wrap($limiterResponse))->map(function ($limit) {
                    return (object) [
                        'key' => md5($this->limiterName.$limit->key),
                        'maxAttempts' => $limit->maxAttempts,
                        'decayMinutes' => $limit->decayMinutes,
                    ];
                })->all()
            );
        } else {
            return $next($job);
        }
    }

    /**
     * Handle a rate limited job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @param  array  $limits
     * @return mixed
     */
    protected function handleJob($job, $next, array $limits)
    {
        foreach ($limits as $limit) {
            if ($this->limiter->tooManyAttempts($limit->key, $limit->maxAttempts)) {
                return $job->release($this->getTimeUntilNextRetry($limit->key));
            }

            $this->limiter->hit($limit->key, $limit->decayMinutes * 60);
        }

        return $next($job);
    }

    /**
     * Get the number of seconds until the next retry.
     *
     * @param  string  $key
     * @return int
     */
    protected function getTimeUntilNextRetry($key)
    {
        return $this->limiter->availableIn($key);
    }
}
