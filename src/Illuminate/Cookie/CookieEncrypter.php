<?php

namespace Illuminate\Cookie;

class CookieEncrypter
{
    /**
     * Encrypt the cookie key.
     *
     * @param  string  $key
     * @return string
     */
    public static function encryptKey($key)
    {
        return sha1($key.'v2');
    }

    /**
     * Encrypt the cookie value.
     *
     * @param  string  $key
     * @param  string  $value
     * @return string
     */
    public static function encryptValue($key, $value)
    {
        return static::encryptKey($key).'|'.$value;
    }

    /**
     * Decrypt the given cookie value and check the given key.
     *
     * @param  string  $value
     * @param  string|null  $key
     * @return string|null
     */
    public static function decryptValue($value, $key = null)
    {
        if ($key !== null && self::hasEncryptedKey($key, $value)) {
            return;
        }

        return substr($value, 41);
    }

    /**
     * Determine if the given value has an encrypted cookie key.
     *
     * @param  string  $key
     * @param  string  $value
     * @return string
     */
    public static function hasEncryptedKey($key, $value)
    {
        return strpos($value, static::encryptKey($key).'|') !== 0;
    }
}
