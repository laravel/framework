<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Cache\TaggedCache tags(array|mixed $names)
 * @method static \Illuminate\Contracts\Cache\Lock lock(string $name, int $seconds = 0, mixed $owner = null)
 * @method static \Illuminate\Contracts\Cache\Lock restoreLock(string $name, string $owner)
 * @method static \Illuminate\Contracts\Cache\Repository  store(string|null $name = null)
 * @method static \Illuminate\Contracts\Cache\Store getStore()
 * @method static bool add(string $key, $value, \DateTimeInterface|\DateInterval|int $ttl = null)
 * @method static bool flush()
 * @method static bool forever(string $key, $value)
 * @method static bool forget(string $key)
 * @method static bool has(string $key)
 * @method static bool missing(string $key)
 * @method static bool put(string $key, $value, \DateTimeInterface|\DateInterval|int $ttl = null)
 * @method static int|bool decrement(string $key, $value = 1)
 * @method static int|bool increment(string $key, $value = 1)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static mixed remember(string $key, \DateTimeInterface|\DateInterval|int $ttl, \Closure $callback)
 * @method static mixed rememberForever(string $key, \Closure $callback)
 * @method static mixed sear(string $key, \Closure $callback)
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
