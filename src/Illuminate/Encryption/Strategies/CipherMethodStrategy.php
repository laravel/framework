<?php

namespace Illuminate\Encryption\Strategies;

interface CipherMethodStrategy
{
    /**
     * Get cipher name.
     *
     * @return string
     */
    public function getCipher() :string;

    /**
     * Get key.
     *
     * @return string
     */
    public function getKey() :string;

    /**
     * Generate key.
     *
     * @return mixed
     */
    public function generateKey();

    /**
     * Check for key length (the name is kept for backward compatibility).
     *
     * @return mixed
     */
    public function supported();
}
