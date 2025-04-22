<?php

namespace Illuminate\Queue\Middleware;

use Closure;

class Skip
{
    public function __construct(protected Closure|bool $skip = false)
    {
    }

    /**
     * Apply the middleware if the given condition is truthy.
     *
     * @param  bool|Closure(): bool  $condition
     */
    public static function when(Closure|bool $condition): self
    {
        return new self($condition);
    }

    /**
     * Apply the middleware unless the given condition is truthy.
     *
     * @param  bool|Closure(): bool  $condition
     */
    public static function unless(Closure|bool $condition): self
    {
        return new self(fn() =>! value($condition));
    }

    /**
     * Handle the job.
     */
    public function handle(mixed $job, callable $next): mixed
    {
        if (value($this->skip)) {
            return false;
        }

        return $next($job);
    }
}
