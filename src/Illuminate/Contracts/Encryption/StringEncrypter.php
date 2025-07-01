<?php

namespace Illuminate\Contracts\Encryption;

interface StringEncrypter
{
    /**
     * Encrypt a string without serialization.
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function encryptString(#[\SensitiveParameter] string $value, bool $deterministic = false): string;

    /**
     * Decrypt the given string without unserialization.
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decryptString(string $payload): string;
}
