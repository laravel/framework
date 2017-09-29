<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Symfony\Component\HttpFoundation\Cookie make(string $name, string $value, int $minutes, string $path, string $domain, bool $secure, bool $httpOnly, bool $raw, string | null $sameSite) Create a new cookie instance.
 * @method static \Symfony\Component\HttpFoundation\Cookie forever(string $name, string $value, string $path, string $domain, bool $secure, bool $httpOnly, bool $raw, string | null $sameSite) Create a cookie that lasts "forever" (five years).
 * @method static \Symfony\Component\HttpFoundation\Cookie forget(string $name, string $path, string $domain) Expire the given cookie.
 * @method static bool hasQueued(string $key) Determine if a cookie has been queued.
 * @method static \Symfony\Component\HttpFoundation\Cookie queued(string $key, mixed $default) Get a queued cookie instance.
 * @method static void queue(array $parameters) Queue a cookie to send with the next response.
 * @method static void unqueue(string $name) Remove a cookie from the queue.
 * @method static $this setDefaultPathAndDomain(string $path, string $domain, bool $secure, string $sameSite) Set the default path and domain for the jar.
 * @method static array getQueuedCookies() Get the cookies which have been queued for the next request.
 *
 * @see \Illuminate\Cookie\CookieJar
 */
class Cookie extends Facade
{
    /**
     * Determine if a cookie exists on the request.
     *
     * @param  string $key
     *
     * @return bool
     */
    public static function has($key)
    {
        return !is_null(static::$app['request']->cookie($key, null));
    }

    /**
     * Retrieve a cookie from the request.
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return string
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
