<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Contracts\Cache\Repository  store(string|null $name = null)
 * @method static bool has(string $key)
 * @method static bool missing(string $key)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static void put(string $key, $value, \DateTimeInterface|\DateInterval|int $seconds)
 * @method static bool add(string $key, $value, \DateTimeInterface|\DateInterval|int $seconds)
 * @method static int|bool increment(string $key, $value = 1)
 * @method static int|bool decrement(string $key, $value = 1)
 * @method static void forever(string $key, $value)
 * @method static mixed remember(string $key, \DateTimeInterface|\DateInterval|int $seconds, \Closure $callback)
 * @method static mixed sear(string $key, \Closure $callback)
 * @method static mixed rememberForever(string $key, \Closure $callback)
 * @method static bool forget(string $key)
 * @method static \Illuminate\Contracts\Cache\Store getStore()
 *
 * @see \Illuminate\Cache\CacheManager
 * @see \Illuminate\Cache\Repository
 */
class Cache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cache';
    }
}
