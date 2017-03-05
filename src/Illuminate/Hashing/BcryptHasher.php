<?php

namespace Illuminate\Hashing;

use LengthException;
use RuntimeException;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class BcryptHasher implements HasherContract
{
    /**
     * Bcrypt truncates strings at 72 characters when computing hashes.
     *
     * @var int
     */
    const BCRYPT_MAX_LENGTH = 72;

    /**
     * Default crypt cost factor.
     *
     * @var int
     */
    protected $rounds = 10;

    /**
     * Hash the given value.
     *
     * @param  string  $value
     * @param  array   $options
     * @return string
     *
     * @throws \LengthException
     * @throws \RuntimeException
     */
    public function make($value, array $options = [])
    {
        if (strlen($value) > static::BCRYPT_MAX_LENGTH && ! isset($options['ignore_password_length'])) {
            throw new LengthException(sprintf('Passwords longer than %d characters are truncated when using Bcrypt.', static::BCRYPT_MAX_LENGTH));
        }

        $cost = isset($options['rounds']) ? $options['rounds'] : $this->rounds;

        $hash = password_hash($value, PASSWORD_BCRYPT, ['cost' => $cost]);

        if ($hash === false) {
            throw new RuntimeException('Bcrypt hashing not supported.');
        }

        return $hash;
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
        if (strlen($hashedValue) === 0) {
            return false;
        }

        return password_verify($value, $hashedValue);
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
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, [
            'cost' => isset($options['rounds']) ? $options['rounds'] : $this->rounds,
        ]);
    }

    /**
     * Set the default password work factor.
     *
     * @param  int  $rounds
     * @return $this
     */
    public function setRounds($rounds)
    {
        $this->rounds = (int) $rounds;

        return $this;
    }
}
