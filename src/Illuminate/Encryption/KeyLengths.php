<?php

namespace Illuminate\Encryption;

class KeyLengths
{
    /**
     * An array of supported ciphers with allowed key lengths.
     *
     * @var array
     */
    protected static $lengths = [
        'AES-128-CBC' => [16, 32],
        'AES-256-CBC' => [32],
    ];

    /**
     * Throw an exception if the given key is invalid.
     *
     * This ensures that the given key has a valid length for the chosen cipher,
     * while also taking into account backwards compatibility (v5.0 generated
     * 32 byte keys for the AES-128-CBC-cipher).
     *
     * @param string $cipher
     * @param string $key
     * @return void
     *
     * @throws \RuntimeException
     */
    public static function ensureValid($cipher, $key)
    {
        $length = mb_strlen($key, '8bit');

        if (isset(static::$lengths[$cipher]) && in_array($length, static::$lengths[$cipher])) {
            return;
        }

        $validCiphers = implode(', ', array_keys(static::$lengths));
        throw new \RuntimeException("The only supported ciphers are [$validCiphers] with the correct key lengths.");

    }

    /**
     * Determine the preferred key length for the given cipher.
     *
     * @param string $cipher
     * @return int
     */
    public static function of($cipher)
    {
        return isset(static::$lengths[$cipher]) ? static::$lengths[$cipher][0] : 32;
    }
}
