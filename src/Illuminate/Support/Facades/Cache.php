<?php

namespace Illuminate\Support\Facades;

/**
 * @method static bool add(string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool clear()
 * @method static int|bool decrement(string $key, mixed $value = 1)
 * @method static bool delete()
 * @method static bool deleteMultiple()
 * @method static \Illuminate\Contracts\Cache\Repository driver(string|null $driver = null)
 * @method static \Illuminate\Cache\CacheManager extend(string $driver, \Closure $callback)
 * @method static void flushMacros()
 * @method static bool forever(string $key, mixed $value)
 * @method static bool forget(string $key)
 * @method static \Illuminate\Cache\CacheManager forgetDriver(array|string|null $name = null)
 * @method static mixed get(array|string $key, mixed $default = null)
 * @method static int|null getDefaultCacheTime()
 * @method static string getDefaultDriver()
 * @method static \Illuminate\Contracts\Events\Dispatcher getEventDispatcher()
 * @method static iterable getMultiple()
 * @method static \Illuminate\Contracts\Cache\Store getStore()
 * @method static bool has(string $key)
 * @method static bool hasMacro(string $name)
 * @method static int|bool increment(string $key, mixed $value = 1)
 * @method static void macro(string $name, object|callable $macro)
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static array many(array $keys)
 * @method static bool missing(string $key)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool offsetExists(string $key)
 * @method static mixed offsetGet(string $key)
 * @method static void offsetSet(string $key, mixed $value)
 * @method static void offsetUnset(string $key)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static void purge(string|null $name = null)
 * @method static bool put(array|string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool putMany(array $values, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static void refreshEventDispatcher()
 * @method static mixed remember(string $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl, \Closure $callback)
 * @method static mixed rememberForever(string $key, \Closure $callback)
 * @method static \Illuminate\Cache\Repository repository(\Illuminate\Contracts\Cache\Store $store)
 * @method static mixed sear(string $key, \Closure $callback)
 * @method static bool set()
 * @method static \Illuminate\Cache\Repository setDefaultCacheTime(int|null $seconds)
 * @method static void setDefaultDriver(string $name)
 * @method static void setEventDispatcher(\Illuminate\Contracts\Events\Dispatcher $events)
 * @method static bool setMultiple()
 * @method static \Illuminate\Contracts\Cache\Repository store(string|null $name = null)
 * @method static bool supportsTags()
 * @method static \Illuminate\Cache\TaggedCache tags(array|mixed $names)
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
