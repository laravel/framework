<?php

namespace Illuminate\Support\Facades;

/**
 * @method static void ageFlashData()
 * @method static array all()
 * @method static string|null blockDriver()
 * @method static int decrement(string $key, int $amount = 1)
 * @method static mixed driver(string|null $driver = null)
 * @method static bool exists(string|array $key)
 * @method static \Illuminate\Session\SessionManager extend(string $driver, \Closure $callback)
 * @method static void flash(string $key, mixed $value = true)
 * @method static void flashInput(array $value)
 * @method static void flush()
 * @method static void forget(string|array $keys)
 * @method static \Illuminate\Session\SessionManager forgetDrivers()
 * @method static mixed get(string $key, mixed $default = null)
 * @method static \Illuminate\Contracts\Container\Container getContainer()
 * @method static string getDefaultDriver()
 * @method static array getDrivers()
 * @method static \SessionHandlerInterface getHandler()
 * @method static string getId()
 * @method static string getName()
 * @method static mixed getOldInput(string|null $key = null, mixed $default = null)
 * @method static array getSessionConfig()
 * @method static bool handlerNeedsRequest()
 * @method static bool has(string|array $key)
 * @method static bool hasOldInput(string|null $key = null)
 * @method static mixed increment(string $key, int $amount = 1)
 * @method static bool invalidate()
 * @method static bool isStarted()
 * @method static bool isValidId(string $id)
 * @method static void keep(array|mixed $keys = null)
 * @method static bool migrate(bool $destroy = false)
 * @method static bool missing(string|array $key)
 * @method static void now(string $key, mixed $value)
 * @method static array only(array $keys)
 * @method static void passwordConfirmed()
 * @method static string|null previousUrl()
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static void push(string $key, mixed $value)
 * @method static void put(string|array $key, mixed $value = null)
 * @method static void reflash()
 * @method static bool regenerate(bool $destroy = false)
 * @method static void regenerateToken()
 * @method static mixed remember(string $key, \Closure $callback)
 * @method static mixed remove(string $key)
 * @method static void replace(array $attributes)
 * @method static void save()
 * @method static \Illuminate\Session\SessionManager setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static void setDefaultDriver(string $name)
 * @method static void setExists(bool $value)
 * @method static void setId(string $id)
 * @method static void setName(string $name)
 * @method static void setPreviousUrl(string $url)
 * @method static void setRequestOnHandler(\Illuminate\Http\Request $request)
 * @method static bool shouldBlock()
 * @method static bool start()
 * @method static string token()
 *
 * @see \Illuminate\Session\SessionManager
 * @see \Illuminate\Session\Store
 */
class Session extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'session';
    }
}
