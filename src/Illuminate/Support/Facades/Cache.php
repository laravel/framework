<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Contracts\Cache\Repository store(string | null $name) Get a cache store instance by name.
 * @method static mixed driver(string $driver) Get a cache driver instance.
 * @method static \Illuminate\Cache\Repository repository(\Illuminate\Contracts\Cache\Store $store) Create a new cache repository with the given implementation.
 * @method static string getDefaultDriver() Get the default cache driver name.
 * @method static void setDefaultDriver(string $name) Set the default cache driver name.
 * @method static $this extend(string $driver, \Closure $callback) Register a custom driver creator Closure.
 * @method static bool has(string $key) Determine if an item exists in the cache.
 * @method static mixed get(string $key, mixed $default) Retrieve an item from the cache by key.
 * @method static array many(array $keys) Retrieve multiple items from the cache by key.
 * @method static mixed pull(string $key, mixed $default) Retrieve an item from the cache and delete it.
 * @method static void put(string $key, mixed $value, \DateTimeInterface | \DateInterval | float | int $minutes) Store an item in the cache.
 * @method static void putMany(array $values, \DateTimeInterface | \DateInterval | float | int $minutes) Store multiple items in the cache for a given number of minutes.
 * @method static bool add(string $key, mixed $value, \DateTimeInterface | \DateInterval | float | int $minutes) Store an item in the cache if the key does not exist.
 * @method static int|bool increment(string $key, mixed $value) Increment the value of an item in the cache.
 * @method static int|bool decrement(string $key, mixed $value) Decrement the value of an item in the cache.
 * @method static void forever(string $key, mixed $value) Store an item in the cache indefinitely.
 * @method static mixed remember(string $key, \DateTimeInterface | \DateInterval | float | int $minutes, \Closure $callback) Get an item from the cache, or store the default value.
 * @method static mixed sear(string $key, \Closure $callback) Get an item from the cache, or store the default value forever.
 * @method static mixed rememberForever(string $key, \Closure $callback) Get an item from the cache, or store the default value forever.
 * @method static bool forget(string $key) Remove an item from the cache.
 * @method static bool True on success and false on failure. clear() Wipes clean the entire cache's keys.
 * @method static \Illuminate\Cache\TaggedCache tags(array | mixed $names) Begin executing a new tags operation if the store supports it.
 * @method static float|int getDefaultCacheTime() Get the default cache time.
 * @method static $this setDefaultCacheTime(float | int $minutes) Set the default cache time in minutes.
 * @method static \Illuminate\Contracts\Cache\Store getStore() Get the cache store implementation.
 * @method static void setEventDispatcher(\Illuminate\Contracts\Events\Dispatcher $events) Set the event dispatcher instance.
 * @method static bool offsetExists(string $key) Determine if a cached value exists.
 * @method static mixed offsetGet(string $key) Retrieve an item from the cache by key.
 * @method static void offsetSet(string $key, mixed $value) Store an item in the cache for the default time.
 * @method static void offsetUnset(string $key) Remove an item from the cache.
 * @method static void macro(string $name, object | callable $macro) Register a custom macro.
 * @method static void mixin(object $mixin) Mix another object into the class.
 * @method static bool hasMacro(string $name) Checks if macro is registered.
 * @method static mixed macroCall(string $method, array $parameters) Dynamically handle calls to the class.
 * @method static \Illuminate\Contracts\Cache\Lock lock(string $name, int $seconds) Get a lock instance.
 * @method static bool flush() Remove all items from the cache.
 * @method static \Predis\ClientInterface connection() Get the Redis connection instance.
 * @method static void setConnection(string $connection) Set the connection name to be used.
 * @method static \Illuminate\Contracts\Redis\Factory getRedis() Get the Redis database instance.
 * @method static string getPrefix() Get the cache key prefix.
 * @method static void setPrefix(string $prefix) Set the cache key prefix.
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
