<?php

namespace Illuminate\Routing\Attributes\Controllers;

use Attribute;
use Illuminate\Routing\Middleware\ThrottleRequests;
use UnitEnum;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Throttle extends Middleware
{
    /**
     * Create a new throttle attribute.
     *
     * When $limiter is a string or enum it is treated as a named rate-limiter
     * (e.g. 'api') and resolves to `ThrottleRequests::using($limiter)`.
     *
     * When $limiter is an integer it is treated as a raw attempt count and
     * resolves to `ThrottleRequests::with($limiter, $decayMinutes)`.
     *
     * @param  int|string|\UnitEnum  $limiter  Named limiter or max attempts per window.
     * @param  int  $decayMinutes  Decay window in minutes (only used when $limiter is an int).
     * @param  array<string>|null  $only  Limit to these controller methods.
     * @param  array<string>|null  $except  Exclude these controller methods.
     */
    public function __construct(
        int|string|UnitEnum $limiter = 'api',
        int $decayMinutes = 1,
        ?array $only = null,
        ?array $except = null,
    ) {
        $middleware = is_int($limiter)
            ? ThrottleRequests::with($limiter, $decayMinutes)
            : ThrottleRequests::using($limiter);

        parent::__construct($middleware, $only, $except);
    }
}
