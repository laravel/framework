<?php

namespace Illuminate\Encryption;

trait CipherMethods
{
    /**
     * Get key length.
     *
     * @return int
     */
    abstract public function getLength() :int;

    /**
     * Get cipher name.
     *
     * @return string
     */
    abstract public function getCipher() :string;

    /**
     * Get key.
     *
     * @return string
     */
    abstract public function getKey() :string;

    /**
     * Check for key length (the name is kept for backward compatibility).
     *
     * @throws \RuntimeException
     */
    public function supported()
    {
        $length = mb_strlen($this->getKey(), '8bit');
        if ($length !== $this->getLength()) {
            throw new \RuntimeException($this->getCipher().' needs exactly 16 characters length key.');
        }
    }

    /**
     * Set key.
     *
     * @param string $key
     */
    abstract public function setKey(string $key);

    /**
     * Generate a random key.
     */
    public function generateKey()
    {
        $this->setKey(random_bytes($this->getLength()));
    }
}
