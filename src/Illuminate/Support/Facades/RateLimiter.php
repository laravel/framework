<?php

namespace Illuminate\Support\Facades;

/**
 * @method static mixed attempt(string $key, int $maxAttempts, \Closure $callback, int $decaySeconds = 60)
 * @method static mixed attempts(string $key)
 * @method static int availableIn(string $key)
 * @method static string cleanRateLimiterKey(string $key)
 * @method static void clear(string $key)
 * @method static \Illuminate\Cache\RateLimiter for(string $name, \Closure $callback)
 * @method static int hit(string $key, int $decaySeconds = 60)
 * @method static \Closure limiter(string $name)
 * @method static int remaining(string $key, int $maxAttempts)
 * @method static mixed resetAttempts(string $key)
 * @method static int retriesLeft(string $key, int $maxAttempts)
 * @method static bool tooManyAttempts(string $key, int $maxAttempts)
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
