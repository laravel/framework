<?php
/**
 * Created by PhpStorm.
 * User: serabalint
 * Date: 2018. 05. 27.
 * Time: 17:21
 */

namespace Illuminate\Encryption;


trait CipherMethods
{
    public abstract function getLength() :int;
    public abstract function getCipher() :string;
    public abstract function getKey() :string;

    /**
     * Check key length. I kept this name for backward compatibility
     */
    public function supported()
    {
        $length = mb_strlen($this->getKey(), '8bit');
        if ($length !== $this->getLength()) {
            throw new \RuntimeException($this->getCipher().' needs exactly 16 characters length key.');
        }
    }

    public abstract function setKey(string $key);

    /**
     * Generate a random key
     */
    public function generateKey()
    {
        $this->setKey(random_bytes($this->getLength()));
    }
}