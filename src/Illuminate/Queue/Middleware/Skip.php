<?php

namespace Illuminate\Queue\Middleware;

use Closure;

class Skip
{
    public function __construct(protected bool $skip = false)
    {
    }

    /**
     * Apply the middleware if the given condition is truthy.
     *
     * @param bool|Closure(): bool $condition
     */
    public static function if(bool|Closure $condition): self
    {
        $condition = $condition instanceof Closure ? $condition() : $condition;

        return new self($condition);
    }

    /**
     * Apply the middleware unless the given condition is truthy.
     *
     * @param bool|Closure(): bool $condition
     */
    public static function unless(bool|Closure $condition): self
    {
        $condition = $condition instanceof Closure ? $condition() : $condition;

        return new self(! $condition);
    }

    public function handle(mixed $job, callable $next): mixed
    {
        if ($this->skip) {
            return false;
        }

        return $next($job);
    }
}
