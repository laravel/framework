<?php

namespace Illuminate\Queue\Middleware;

use Closure;

class Release
{
    /**
     * @param  bool  $release  Whether the job should be released back onto the queue.
     * @param  int  $retryAfter  The number of seconds to wait before retrying the job.
     */
    public function __construct(
        protected bool $release = false,
        protected int $retryAfter = 0,
    ) {
    }

    /**
     * Release the job back onto the queue if the given condition is truthy.
     *
     * @param  bool|(\Closure(): bool)  $condition
     * @param  int  $retryAfter  The number of seconds to wait before retrying the job.
     */
    public static function when(Closure|bool $condition, int $retryAfter = 0): static
    {
        return new static(value($condition), $retryAfter);
    }

    /**
     * Release the job back onto the queue unless the given condition is truthy.
     *
     * @param  bool|(\Closure(): bool)  $condition
     * @param  int  $retryAfter  The number of seconds to wait before retrying the job.
     */
    public static function unless(Closure|bool $condition, int $retryAfter = 0): static
    {
        return new static(! value($condition), $retryAfter);
    }

    /**
     * Handle the job.
     */
    public function handle(mixed $job, callable $next): mixed
    {
        if ($this->release) {
            return $job->release($this->retryAfter);
        }

        return $next($job);
    }
}
