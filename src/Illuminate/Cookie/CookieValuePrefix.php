<?php

namespace Illuminate\Cookie;

class CookieValuePrefix
{
    /**
     * Create a new cookie value prefix for the given cookie name.
     *
     * @param  string  $cookieName
     * @param  string  $key
     * @return string
     */
    public static function create($cookieName, $key)
    {
        return hash_hmac('sha1', $cookieName.'v2', $key).'|';
    }

    /**
     * Remove the cookie value prefix.
     *
     * @param  string  $cookieValue
     * @return string
     */
    public static function remove($cookieValue)
    {
        return substr($cookieValue, 41);
    }

    /**
     * Validate a cookie value contains a valid prefix. If it does, return the cookie value with the prefix removed. Otherwise, return null.
     *
     * @param  string  $cookieName
     * @param  string  $cookieValue
     * @param  string  $key
     * @return string|null
     */
    public static function validate($cookieName, $cookieValue, $key)
    {
        $hasValidPrefix = str_starts_with($cookieValue, static::create($cookieName, $key));

        return $hasValidPrefix ? static::remove($cookieValue) : null;
    }
}
