<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Cache\RateLimiter for(\BackedEnum|\UnitEnum|string $name, \Closure $callback)
 * @method static \Closure|null limiter(\BackedEnum|\UnitEnum|string $name)
 * @method static mixed attempt(string $key, int $maxAttempts, \Closure $callback, int $decaySeconds = 60)
 * @method static bool tooManyAttempts(string $key, int $maxAttempts)
 * @method static int hit(string $key, int $decaySeconds = 60)
 * @method static int increment(string $key, int $decaySeconds = 60, int $amount = 1)
 * @method static int decrement(string $key, int $decaySeconds = 60, int $amount = 1)
 * @method static mixed attempts(string $key)
 * @method static mixed resetAttempts(string $key)
 * @method static int remaining(string $key, int $maxAttempts)
 * @method static int retriesLeft(string $key, int $maxAttempts)
 * @method static void clear(string $key)
 * @method static int availableIn(string $key)
 * @method static string cleanRateLimiterKey(string $key)
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
