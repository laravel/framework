<?php

namespace Illuminate\Contracts\Encryption;

interface Encrypter
{
    /**
     * Encrypt the given value.
     *
     * @param  mixed  $value
     * @param  bool  $serialize
     * @return mixed
     */
    public function encrypt($value, $serialize = true);

    /**
     * Decrypt the given value.
     *
     * @param  mixed  $payload
     * @param  bool  $unserialize
     * @return mixed
     */
    public function decrypt($payload, $unserialize = true);

    /**
     * Generate a new key for the chosen cipher.
     *
     * @param  string  $cipher
     * @return mixed
     */
    public function generateKey($cipher = null);

    /**
     * Determine whether the encrypter is valid.
     *
     * @return bool
     */
    public function isValid();
}
