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
     * Encrypt a string without serialization.
     *
     * @param  string  $value
     * @return string
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function encryptString($value);

    /**
     * Decrypt the given value.
     *
     * @param  mixed  $payload
     * @param  bool  $unserialize
     * @return mixed
     */
    public function decrypt($payload, $unserialize = true);

    /**
     * Decrypt the given string without unserialization.
     *
     * @param  string  $payload
     * @return string
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decryptString($payload);

    /**
     * Generate a new key for the chosen cipher.
     *
     * @param  string|null  $cipher
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
