<?php
/**
 * Created by PhpStorm.
 * User: serabalint
 * Date: 2018. 05. 26.
 * Time: 19:14
 */

namespace Illuminate\Encryption\Strategies;


class Aes256CBC implements EncryptStrategy
{
    protected $key;

    const LENGTH = 32;
    const CIPHER = 'AES-256-CBC';

    public function __construct(string $key = null)
    {
        if ($key) {
            $this->key = $key;
        } else {
            $this->generateKey();
        }
    }

    public function getCipher(): string
    {
        return self::CIPHER;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function generateKey()
    {
        $this->key = random_bytes(self::LENGTH);
    }

    public function supported()
    {
        $length = mb_strlen($this->key, '8bit');
        if ($length !== self::LENGTH) {
            throw new \RuntimeException(self::CIPHER.' needs exactly 16 characters length key.');
        }
    }
}