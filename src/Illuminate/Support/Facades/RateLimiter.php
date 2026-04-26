<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Cache\RateLimiter for(\UnitEnum|string $name, \Closure $callback)
 * @method static \Closure|null limiter(\UnitEnum|string $name)
 * @method static mixed attempt(\UnitEnum|string $key, int $maxAttempts, \Closure $callback, \DateTimeInterface|\DateInterval|int $decaySeconds = 60)
 * @method static bool tooManyAttempts(\UnitEnum|string $key, int $maxAttempts)
 * @method static int hit(\UnitEnum|string $key, \DateTimeInterface|\DateInterval|int $decaySeconds = 60)
 * @method static int increment(\UnitEnum|string $key, \DateTimeInterface|\DateInterval|int $decaySeconds = 60, int $amount = 1)
 * @method static int decrement(\UnitEnum|string $key, \DateTimeInterface|\DateInterval|int $decaySeconds = 60, int $amount = 1)
 * @method static mixed attempts(\UnitEnum|string $key)
 * @method static bool resetAttempts(\UnitEnum|string $key)
 * @method static int remaining(\UnitEnum|string $key, int $maxAttempts)
 * @method static int retriesLeft(\UnitEnum|string $key, int $maxAttempts)
 * @method static void clear(\UnitEnum|string $key)
 * @method static int availableIn(\UnitEnum|string $key)
 * @method static string cleanRateLimiterKey(\UnitEnum|string $key)
 *
 * @see \Illuminate\Cache\RateLimiter
 */
class RateLimiter extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Illuminate\Cache\RateLimiter::class;
    }
}
