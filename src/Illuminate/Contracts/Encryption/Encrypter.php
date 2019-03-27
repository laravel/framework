<?php

namespace Illuminate\Contracts\Encryption;

interface Encrypter
{
    /**
     * Encrypt the given value.
     *
     * @param mixed $value
     * @param bool  $serialize
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     *
     * @return mixed
     */
    public function encrypt($value, $serialize = true);

    /**
     * Decrypt the given value.
     *
     * @param mixed $payload
     * @param bool  $unserialize
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     *
     * @return mixed
     */
    public function decrypt($payload, $unserialize = true);
}
