<?php

namespace Illuminate\Hashing;

abstract class AbstractHasher
{
    /**
     * Get information about the given hashed value.
     *
     * @param  string  $hashedValue
     * @return array
     */
    public function info($hashedValue)
    {
        return password_get_info($hashedValue);
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param  string  $value
     * @param  string|null  $hashedValue
     * @param  array  $options
     * @return bool
     */
    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = [])
    {
        if (is_null($hashedValue) || strlen($hashedValue) === 0) {
            return false;
        }

        return password_verify($value, $hashedValue);
    }

    /**
     * Determine if a given string is already hashed.
     *
     * @param  string  $value
     * @return bool
     */
    public function isHashed(#[\SensitiveParameter] $value)
    {
        return $this->info($value)['algo'] !== null;
    }
}
