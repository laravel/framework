<?php

namespace Illuminate\Encryption;


trait CipherMethods
{
    abstract public function getLength() :int;
    abstract public function getCipher() :string;
    abstract public function getKey() :string;

    /**
     * Check key length. I kept this name for backward compatibility.
     */
    public function supported()
    {
        $length = mb_strlen($this->getKey(), '8bit');
        if ($length !== $this->getLength()) {
            throw new \RuntimeException($this->getCipher().' needs exactly 16 characters length key.');
        }
    }

    abstract public function setKey(string $key);

    /**
     * Generate a random key.
     */
    public function generateKey()
    {
        $this->setKey(random_bytes($this->getLength()));
    }
}