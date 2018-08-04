<?php

namespace Illuminate\Hashing;

use RuntimeException;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class ArgonHasher extends AbstractHasher implements HasherContract
{
    /**
     * The default memory cost factor.
     *
     * @var int
     */
    protected $memory = 1024;

    /**
     * The default time cost factor.
     *
     * @var int
     */
    protected $time = 2;

    /**
     * The default threads factor.
     *
     * @var int
     */
    protected $threads = 2;

    /**
     * The default Argon hashing type.
     *
     * @var string
     */
    protected $type = 'argon2i';

    /**
     * The Argon supported hashing types.
     *
     * @var array
     */
    protected $supportedTypes = ['argon2i', 'argon2id'];

    /**
     * Create a new hasher instance.
     *
     * @param  array  $options
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->time = $options['time'] ?? $this->time;
        $this->memory = $options['memory'] ?? $this->memory;
        $this->threads = $options['threads'] ?? $this->threads;
        $this->type = $options['type'] ?? $this->type;

        $this->ensureValidHashingType($this->type);
    }

    /**
     * Hash the given value.
     *
     * @param  string  $value
     * @param  array  $options
     * @return string
     *
     * @throws \RuntimeException
     */
    public function make($value, array $options = [])
    {
        $hash = password_hash($value, $this->getHashingAlgorithmConstant($options), [
            'memory_cost' => $this->memory($options),
            'time_cost' => $this->time($options),
            'threads' => $this->threads($options),
        ]);

        if ($hash === false) {
            throw new RuntimeException('Argon2 hashing not supported.');
        }

        return $hash;
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param  string  $value
     * @param  string  $hashedValue
     * @param  array  $options
     * @return bool
     */
    public function check($value, $hashedValue, array $options = [])
    {
        if (! in_array($this->info($hashedValue)['algoName'], $this->supportedTypes)) {
            throw new RuntimeException('This password does not use the Argon algorithm.');
        }

        return parent::check($value, $hashedValue, $options);
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param  string  $hashedValue
     * @param  array  $options
     * @return bool
     */
    public function needsRehash($hashedValue, array $options = [])
    {
        return password_needs_rehash($hashedValue, $this->getHashingAlgorithmConstant($options), [
            'memory_cost' => $this->memory($options),
            'time_cost' => $this->time($options),
            'threads' => $this->threads($options),
        ]);
    }

    /**
     * Set the default password memory factor.
     *
     * @param  int  $memory
     * @return $this
     */
    public function setMemory(int $memory)
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * Set the default password timing factor.
     *
     * @param  int  $time
     * @return $this
     */
    public function setTime(int $time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Set the default password threads factor.
     *
     * @param  int  $threads
     * @return $this
     */
    public function setThreads(int $threads)
    {
        $this->threads = $threads;

        return $this;
    }

    /**
     * Set the default password hashing type.
     *
     * @param  string  $hashingType
     * @return $this
     */
    public function setHashingType(string $hashingType)
    {
        $this->ensureValidHashingType($hashingType);

        $this->type = $hashingType;

        return $this;
    }

    /**
     * Extract the memory cost value from the options array.
     *
     * @param  array  $options
     * @return int
     */
    protected function memory(array $options)
    {
        return $options['memory'] ?? $this->memory;
    }

    /**
     * Extract the time cost value from the options array.
     *
     * @param  array  $options
     * @return int
     */
    protected function time(array $options)
    {
        return $options['time'] ?? $this->time;
    }

    /**
     * Extract the threads value from the options array.
     *
     * @param  array  $options
     * @return int
     */
    protected function threads(array $options)
    {
        return $options['threads'] ?? $this->threads;
    }

    /**
     * Throws RuntimeException if the hashing type is not valid and supported.
     *
     * @param string $hashingType
     */
    protected function ensureValidHashingType(string $hashingType)
    {
        if (! in_array($hashingType, $this->supportedTypes)) {
            throw new RuntimeException(sprintf('Argon "%s" hashing type is not supported.', $hashingType));
        }
    }

    /**
     * Get the hashing algorithm constant value from the options array.
     *
     * @param  array  $options
     * @return int
     */
    protected function getHashingAlgorithmConstant(array $options)
    {
        $hashingType = $options['type'] ?? $this->type;

        $this->ensureValidHashingType($hashingType);

        if ($hashingType === 'argon2i') {
            return PASSWORD_ARGON2I;
        } else {
            return PASSWORD_ARGON2ID;
        }
    }
}
