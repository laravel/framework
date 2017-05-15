<?php

namespace Illuminate\Hashing;

use Sodium;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class SodiumHasher implements HasherContract
{
    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param  string  $unusedHashedValue
     * @param  array   $unusedOptions
     * @return bool
     */
    public function needsRehash($unusedHashedValue, array $unusedOptions = [])
    {
        return false;
    }

    /**
     * Hash the given value.
     *
     * @param  string  $value
     * @param  array   $unusedOptions
     * @return string
     *
     * @throws \RuntimeException
     */
    public function make($value, array $unusedOptions = [])
    {
        return Sodium\crypto_pwhash_scryptsalsa208sha256_str(
            $value,
            Sodium\CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
            Sodium\CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE
        );
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
        if (Sodium\crypto_pwhash_scryptsalsa208sha256_str_verify($hashedValue, $value)) {
            Sodium\memzero($value);

            return true;
        } else {
            Sodium\memzero($value);

            return false;
        }
    }
}
