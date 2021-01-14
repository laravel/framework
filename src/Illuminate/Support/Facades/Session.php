<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \SessionHandlerInterface getHandler()
 * @method static array all()
 * @method static bool exists(string|array $key)
 * @method static bool handlerNeedsRequest()
 * @method static bool has(string|array $key)
 * @method static bool isStarted()
 * @method static bool migrate(bool $destroy = false)
 * @method static bool save()
 * @method static bool start()
 * @method static mixed get(string $key, $default = null)
 * @method static mixed flash(string $class, string $message)
 * @method static mixed pull(string $key, $default = null)
 * @method static mixed remove(string $key)
 * @method static string getId()
 * @method static string getName()
 * @method static string token()
 * @method static string|null previousUrl()
 * @method static void flush()
 * @method static void forget(string|array $keys)
 * @method static void push(string $key, mixed $value)
 * @method static void put(string|array $key, $value = null)
 * @method static void setId(string $id)
 * @method static void setPreviousUrl(string $url)
 * @method static void setRequestOnHandler(\Illuminate\Http\Request $request)
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
