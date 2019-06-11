<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Hashing\BcryptHasher createBcryptDriver()
 * @method static \Illuminate\Hashing\ArgonHasher createArgonDriver()
 * @method static \Illuminate\Hashing\Argon2IdHasher createArgon2idDriver()
 * @method static array info(string $hashedValue)
 * @method static string make(string $value, array $options = [])
 * @method static bool check(string $value, string $hashedValue, array $options = [])
 * @method static bool needsRehash(string $hashedValue, array $options = [])
 * @method static string getDefaultDriver()
 *
 * @see \Illuminate\Hashing\HashManager
 */
class Hash extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'hash';
    }
}
