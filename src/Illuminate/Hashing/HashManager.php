<?php

namespace Illuminate\Hashing;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Manager;

/**
 * @mixin Hasher
 */
class HashManager extends Manager implements Hasher
{
    /**
     * Create an instance of the Bcrypt hash Driver.
     *
     * @return BcryptHasher
     */
    public function createBcryptDriver(): BcryptHasher
    {
        return new BcryptHasher($this->config->get('hashing.bcrypt') ?? []);
    }

    /**
     * Create an instance of the Argon2i hash Driver.
     *
     * @return ArgonHasher
     */
    public function createArgonDriver(): ArgonHasher
    {
        return new ArgonHasher($this->config->get('hashing.argon') ?? []);
    }

    /**
     * Create an instance of the Argon2id hash Driver.
     *
     * @return Argon2IdHasher
     */
    public function createArgon2idDriver(): Argon2IdHasher
    {
        return new Argon2IdHasher($this->config->get('hashing.argon') ?? []);
    }

    /**
     * Get information about the given hashed value.
     *
     * @param string $hashedValue
     * @return array
     */
    public function info(string $hashedValue): array
    {
        return $this->driver()->info($hashedValue);
    }

    /**
     * Hash the given value.
     *
     * @param string $value
     * @param array $options
     * @return string
     */
    public function make(string $value, array $options = []): string
    {
        return $this->driver()->make($value, $options);
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param string $value
     * @param string|null $hashedValue
     * @param array $options
     * @return bool
     */
    public function check(string $value, string $hashedValue = null, array $options = []): bool
    {
        return $this->driver()->check($value, $hashedValue, $options);
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param string $hashedValue
     * @param array $options
     * @return bool
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return $this->driver()->needsRehash($hashedValue, $options);
    }

    /**
     * Determine if a given string is already hashed.
     *
     * @param string $value
     * @return bool
     */
    public function isHashed(string $value): bool
    {
        return password_get_info($value)['algo'] !== null;
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('hashing.driver', 'bcrypt');
    }
}
