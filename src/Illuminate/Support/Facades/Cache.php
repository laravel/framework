<?php

namespace Illuminate\Support\Facades;

use Mockery;

/**
 * @method static \Illuminate\Contracts\Cache\Repository store(string|null $name = null)
 * @method static \Illuminate\Contracts\Cache\Repository driver(string|null $driver = null)
 * @method static \Illuminate\Contracts\Cache\Repository memo(string|null $driver = null)
 * @method static \Illuminate\Contracts\Cache\Repository resolve(string $name)
 * @method static \Illuminate\Cache\Repository build(array $config)
 * @method static \Illuminate\Cache\Repository repository(\Illuminate\Contracts\Cache\Store $store, array $config = [])
 * @method static void refreshEventDispatcher()
 * @method static string getDefaultDriver()
 * @method static void setDefaultDriver(string $name)
 * @method static \Illuminate\Cache\CacheManager forgetDriver(array|string|null $name = null)
 * @method static void purge(string|null $name = null)
 * @method static \Illuminate\Cache\CacheManager extend(string $driver, \Closure $callback)
 * @method static \Illuminate\Cache\CacheManager setApplication(\Illuminate\Contracts\Foundation\Application $app)
 * @method static bool has(\BackedEnum|\UnitEnum|array|string $key)
 * @method static bool missing(\BackedEnum|\UnitEnum|string $key)
 * @method static mixed get(\BackedEnum|\UnitEnum|array|string $key, mixed $default = null)
 * @method static array many(array $keys)
 * @method static iterable getMultiple(iterable $keys, mixed $default = null)
 * @method static mixed pull(\BackedEnum|\UnitEnum|array|string $key, mixed $default = null)
 * @method static string string(\BackedEnum|\UnitEnum|string $key, \Closure|string|null $default = null)
 * @method static int integer(\BackedEnum|\UnitEnum|string $key, \Closure|int|null $default = null)
 * @method static float float(\BackedEnum|\UnitEnum|string $key, \Closure|float|null $default = null)
 * @method static bool boolean(\BackedEnum|\UnitEnum|string $key, \Closure|bool|null $default = null)
 * @method static array array(\BackedEnum|\UnitEnum|string $key, \Closure|array|null $default = null)
 * @method static bool put(\BackedEnum|\UnitEnum|array|string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool set(\BackedEnum|\UnitEnum|array|string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool putMany(array $values, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool setMultiple(iterable $values, null|int|\DateInterval $ttl = null)
 * @method static bool add(\BackedEnum|\UnitEnum|array|string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static int|bool increment(\BackedEnum|\UnitEnum|string $key, mixed $value = 1)
 * @method static int|bool decrement(\BackedEnum|\UnitEnum|string $key, mixed $value = 1)
 * @method static bool forever(\BackedEnum|\UnitEnum|string $key, mixed $value)
 * @method static mixed remember(\BackedEnum|\UnitEnum|string $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl, \Closure $callback)
 * @method static mixed sear(\BackedEnum|\UnitEnum|string $key, \Closure $callback)
 * @method static mixed rememberForever(\BackedEnum|\UnitEnum|string $key, \Closure $callback)
 * @method static mixed flexible(\BackedEnum|\UnitEnum|string $key, array $ttl, callable $callback, array|null $lock = null, bool $alwaysDefer = false)
 * @method static mixed withoutOverlapping(\BackedEnum|\UnitEnum|string $key, callable $callback, int $lockFor = 0, int $waitFor = 10, string|null $owner = null)
 * @method static bool forget(\BackedEnum|\UnitEnum|array|string $key)
 * @method static bool delete(\BackedEnum|\UnitEnum|array|string $key)
 * @method static bool deleteMultiple(iterable $keys)
 * @method static bool clear()
 * @method static \Illuminate\Cache\TaggedCache tags(mixed $names)
 * @method static string|null getName()
 * @method static bool supportsTags()
 * @method static int|null getDefaultCacheTime()
 * @method static \Illuminate\Cache\Repository setDefaultCacheTime(int|null $seconds)
 * @method static \Illuminate\Contracts\Cache\Store getStore()
 * @method static \Illuminate\Cache\Repository setStore(\Illuminate\Contracts\Cache\Store $store)
 * @method static \Illuminate\Contracts\Events\Dispatcher|null getEventDispatcher()
 * @method static void setEventDispatcher(\Illuminate\Contracts\Events\Dispatcher $events)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static bool flush()
 * @method static string getPrefix()
 * @method static \Illuminate\Contracts\Cache\Lock lock(string $name, int $seconds = 0, string|null $owner = null)
 * @method static \Illuminate\Contracts\Cache\Lock restoreLock(string $name, string $owner)
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

    /**
     * Convert the facade into a Mockery spy.
     *
     * @return \Mockery\MockInterface
     */
    public static function spy()
    {
        if (! static::isMock()) {
            $class = static::getMockableClass();
            $instance = static::getFacadeRoot();

            if ($class && $instance) {
                return tap(Mockery::spy($instance)->makePartial(), function ($spy) {
                    static::swap($spy);
                });
            }

            return tap($class ? Mockery::spy($class) : Mockery::spy(), function ($spy) {
                static::swap($spy);
            });
        }
    }
}
