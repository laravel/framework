<?php

namespace Illuminate\Routing\Exceptions;

use Exception;

class InvalidNamedRateLimiterException extends Exception
{
    /**
     * Create a new exception for invalid named rate limiter.
     *
     * @param  string  $limiter
     * @return static
     */
    public static function forLimiter(string $limiter)
    {
        return new static("Named rate limiter [{$limiter}] is not defined.");
    }
}
