<?php

namespace Illuminate\Support\Facades;

/**
 * @template TCached of mixed
 *
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
 * @method static null|TCached get(string $key, TCached $default = null)
 * @method static null|TCached pull(string $key, TCached $default = null)
 * @method static TCached remember(string $key, \DateTimeInterface|\DateInterval|int $ttl, \Closure(): TCached $callback)
 * @method static TCached rememberForever(string $key, \Closure(): TCached $callback)
 * @method static TCached sear(string $key, \Closure(): TCached $callback)
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
