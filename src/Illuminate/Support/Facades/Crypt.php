<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string encrypt(string $value, bool $serialize = true)
 * @method static string decrypt(string $payload, bool $unserialize = true)
 *
 * @see \Illuminate\Encryption\Encrypter
 */
class Crypt extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'encrypter';
    }
}
