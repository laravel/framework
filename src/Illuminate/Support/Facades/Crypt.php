<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string encrypt($value, bool $serialize = true)
 * @method static string encryptString(string $value)
 * @method static string decrypt($payload, bool $unserialize = true)
 * @method static string decryptString(string $payload)
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
