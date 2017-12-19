<?php

namespace Illuminate\Support\Testing\Fakes;

use PHPUnit\Framework\Assert as PHPUnit;
use Illuminate\Contracts\Hashing\Hasher;

class HashFake implements Hasher
{
    /**
     * The value to be hashed.
     *
     * @var string
     */
    protected $value;

    /**
     * Assert if a hash was pushed based on a truth-test callback.
     *
     * @param  string  $value
     * @return void
     */
    public function assertValueWasHashed($value)
    {
        PHPUnit::assertEquals($this->value, $value, "The expected [{$value}] value was not hashed.");
    }

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
     * Hash the given value.
     *
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function make($value, array $options = [])
    {
        $this->value = $value;

        return $value;
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param  string  $value
     * @param  string  $hashedValue
     * @param  array   $options
     * @return bool
     */
    public function check($value, $hashedValue, array $options = [])
    {
        return $value === $hashedValue;
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param  string  $hashedValue
     * @param  array   $options
     * @return bool
     */
    public function needsRehash($hashedValue, array $options = [])
    {
        return false;
    }
}
