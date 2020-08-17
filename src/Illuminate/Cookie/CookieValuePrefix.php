<?php

namespace Illuminate\Cookie;

use Illuminate\Support\Str;

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
     * Verify the provided cookie's value.
     *
     * @param  string  $name
     * @param  string  $value
     * @param  string  $key
     * @return string|null
     */
    public static function getVerifiedValue($name, $value, $key)
    {
        $verifiedValue = null;

        if (Str::startsWith($value, static::create($name, $key))) {
            $verifiedValue = static::remove($value);
        }

        return $verifiedValue;
    }
}
