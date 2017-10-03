<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string make(string $value, array $options) Hash the given value.
 * @method static bool check(string $value, string $hashedValue, array $options) Check the given plain value against a hash.
 * @method static bool needsRehash(string $hashedValue, array $options) Check if the given hash has been hashed using the given options.
 * @method static $this setRounds(int $rounds) Set the default password work factor.
 *
 * @see \Illuminate\Hashing\BcryptHasher
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
