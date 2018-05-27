<?php
/**
 * Created by PhpStorm.
 * User: serabalint
 * Date: 2018. 05. 26.
 * Time: 19:14
 */

namespace Illuminate\Encryption\Strategies;


use Illuminate\Encryption\CipherMethods;

/**
 * Class Aes128CBC
 * @package Illuminate\Encryption\Strategies
 */
class Aes128CBC implements CipherMethodStrategy
{
    use CipherMethods;
    /**
     * @var string
     */
    protected $key;

    /**
     * Length of key
     */
    const LENGTH = 16;
    /**
     * Name of this cipher method
     */
    const CIPHER = 'AES-128-CBC';

    /**
     * Aes128CBC constructor.
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
     * Get cipher method's name
     *
     * @return string
     */
    public function getCipher(): string
    {
        return self::CIPHER;
    }

    /**
     * Returns the key
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set the key
     *
     * @param string $key
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * Get key length
     *
     * @return int
     */
    public function getLength(): int
    {
        return self::LENGTH;
    }
}