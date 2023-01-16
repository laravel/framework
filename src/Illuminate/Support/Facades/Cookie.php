<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Symfony\Component\HttpFoundation\Cookie make(string $name, string $value, int $minutes = 0, ?string $path = null, ?string $domain = null, bool|null $secure = null, bool $httpOnly = true, bool $raw = false, ?string $sameSite = null)
 * @method static \Symfony\Component\HttpFoundation\Cookie forever(string $name, string $value, ?string $path = null, ?string $domain = null, bool|null $secure = null, bool $httpOnly = true, bool $raw = false, ?string $sameSite = null)
 * @method static \Symfony\Component\HttpFoundation\Cookie forget(string $name, ?string $path = null, ?string $domain = null)
 * @method static bool hasQueued(string $key, ?string $path = null)
 * @method static \Symfony\Component\HttpFoundation\Cookie|null queued(string $key, mixed $default = null, ?string $path = null)
 * @method static void queue(mixed ...$parameters)
 * @method static void expire(string $name, ?string $path = null, ?string $domain = null)
 * @method static void unqueue(string $name, ?string $path = null)
 * @method static \Illuminate\Cookie\CookieJar setDefaultPathAndDomain(string $path, ?string $domain, bool|null $secure = false, ?string $sameSite = null)
 * @method static \Symfony\Component\HttpFoundation\Cookie[] getQueuedCookies()
 * @method static \Illuminate\Cookie\CookieJar flushQueuedCookies()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \Illuminate\Cookie\CookieJar
 */
class Cookie extends Facade
{
    /**
     * Determine if a cookie exists on the request.
     *
     * @param  string  $key
     * @return bool
     */
    public static function has($key)
    {
        return ! is_null(static::$app['request']->cookie($key, null));
    }

    /**
     * Retrieve a cookie from the request.
     *
     * @param  ?string  $key
     * @param  mixed  $default
     * @return string|array|null
     */
    public static function get($key = null, $default = null)
    {
        return static::$app['request']->cookie($key, $default);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cookie';
    }
}
