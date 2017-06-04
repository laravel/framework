<?php

namespace Illuminate\Contracts\Encryption;

interface Encrypter
{
    /**
     * Encrypt the given value.
     *
     * @param  string  $value
     * @param  bool  $serialize
     * @param  string  $key
     * @return string
     */
    public function encrypt($value, $serialize = true, $key = null);

    /**
     * Decrypt the given value.
     *
     * @param  string  $payload
     * @param  bool  $unserialize
     * @param  string  $key
     * @return string
     */
    public function decrypt($payload, $unserialize = true, $key = null);
}
