<?php

namespace Illuminate\Hashing;

use RuntimeException;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class Argon2Hasher implements HasherContract
{
    /**
     * Hash the given value.
     *
     * @param  string $value
     * @param  array  $options
     * @return string
     *
     * @throws \RuntimeException
     */
    public function make($value, array $options = []): string
    {
        if (extension_loaded('sodium')) {
            return sodium_crypto_pwhash_str(
                $value,
                $options['time_cost'] ?? SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
                $options['memory_cost'] ?? SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
            );
        }

        throw new RuntimeException('Argon2i hashing not supported.');
    }

    /**
     * Check a plain text value against a hashed value.
     *
     * @param  string $value
     * @param  string $hashedValue
     * @param  array  $options
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function check($value, $hashedValue, array $options = []): bool
    {
        if (extension_loaded('sodium')) {
            $valid = sodium_crypto_pwhash_str_verify($hashedValue, $value);
            sodium_memzero($value);
            return $valid;
        }

        throw new RuntimeException('Argon2i hashing not supported.');
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param  string $hashedValue
     * @param  array  $options
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function needsRehash($hashedValue, array $options = []): bool
    {
        // Extract options from the hashed value
        list($memoryCost, $timeCost) = sscanf($hashedValue, '$%*[argon2id]$v=%*ld$m=%d,t=%d');
        $hashOptions = ['memory_cost' => $memoryCost, 'time_cost' => $timeCost];

        // Filter unknown options from the options array
        $options = array_filter($options, function ($key) use ($hashOptions) {
            return isset($hashOptions[$key]);
        }, ARRAY_FILTER_USE_KEY);

        return !empty(array_diff_assoc($options, $hashOptions));
    }

    /**
     * Determine if the system supports Argon2i hashing.
     *
     * @return bool
     */
    public function isSupported(): bool
    {
        return extension_loaded('sodium');
    }
}
