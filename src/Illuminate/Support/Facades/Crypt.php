<?php

namespace Illuminate\Support\Facades;

/**
 * @method static bool supported(string $key, string $cipher) Determine if the given key and cipher combination is valid.
 * @method static string generateKey(string $cipher) Create a new encryption key for the given cipher.
 * @method static string encrypt(mixed $value, bool $serialize) Encrypt the given value.
 * @method static string encryptString(string $value) Encrypt a string without serialization.
 * @method static string decrypt(mixed $payload, bool $unserialize) Decrypt the given value.
 * @method static string decryptString(string $payload) Decrypt the given string without unserialization.
 * @method static string getKey() Get the encryption key.
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
