<?php

namespace Illuminate\Support;

use RuntimeException;

class Nonce
{
    /**
     * The nonce value
     *
     * @var string|null
     */
    protected static $nonce = null;

    /**
     * The callable that returns a string as the nonce value
     * @var callable|null
     */
    protected static $nonceProvider = null;

    /**
     * Set a string value or callable as the nonce provider which returns a string
     *
     * @param string|callable $nonce
     * @return void
     */
    public static function setNonce($nonce)
    {
        if (is_string($nonce)) {
            static::$nonce = self::sanitize($nonce);
        }

        if (is_callable($nonce)) {
            static::$nonceProvider = $nonce;
        }
    }

    /**
     * Get the nonce value from stored static variable or resolve it from provider
     *
     * @return string|null
     */
    public static function getNonce()
    {
        if (is_null(self::$nonce) && is_null(self::$nonceProvider)) {
            return null;
        }

        if (is_null(self::$nonce)) {
            self::$nonce = self::sanitize(call_user_func(self::$nonceProvider));
        }

        return self::$nonce;
    }

    /**
     * Check if the nonce value is available
     *
     * @return bool
     */
    public static function hasNonce()
    {
        return ! is_null(self::getNonce());
    }

    /**
     * Reset the nonce value and the provider
     *
     * @return void
     */
    public static function reset()
    {
        self::$nonce = null;

        self::$nonceProvider = null;
    }

    /**
     * Ensure the nonce value is sanitized
     *
     * @param string $nonce
     * @return string
     */
    private static function sanitize($nonce)
    {
        if (! is_string($nonce)) {
            throw new RuntimeException('The nonce must be a string. (For the best practice please use a random base 64 value)');
        }

        $sanitized = trim($nonce);

        if (strlen($sanitized) === 0) {
            throw new RuntimeException('The nonce cannot be an empty string.');
        }

        return $sanitized;
    }
}
