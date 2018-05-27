<?php
/**
 * Created by PhpStorm.
 * User: serabalint
 * Date: 2018. 05. 26.
 * Time: 19:14
 */

namespace Illuminate\Encryption\Strategies;


use Illuminate\Encryption\EncryptorMethods;

class Aes128CBC implements EncryptStrategy
{
    use EncryptorMethods;
    protected $key;

    const LENGTH = 16;
    const CIPHER = 'AES-128-CBC';

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

    public function getLength(): int
    {
        return self::LENGTH;
    }

    public function generateKey()
    {
        $this->key = random_bytes(self::LENGTH);
    }
}