<?php

namespace Illuminate\Encryption\Strategies;

use Illuminate\Encryption\CipherMethods;

/**
 * Class Aes256CBC.
 */
class Aes256CBC implements CipherMethodStrategy
{
    use CipherMethods;
    /**
     * @var string
     */
    protected $key;

    /**
     * Length of key.
     */
    const LENGTH = 32;
    /**
     * Name of this cipher method.
     */
    const CIPHER = 'AES-256-CBC';

    /**
     * Aes256CBC constructor.
     * @param string|null $key
     */
    public function __construct(string $key = null)
    {
        if ($key) {
            $this->key = $key;
        } else {
            $this->generateKey();
        }
    }

    /**
     * Get cipher method's name.
     *
     * @return string
     */
    public function getCipher(): string
    {
        return self::CIPHER;
    }

    /**
     * Get key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set key.
     *
     * @param string $key
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * Get key length.
     *
     * @return int
     */
    public function getLength(): int
    {
        return self::LENGTH;
    }
}
