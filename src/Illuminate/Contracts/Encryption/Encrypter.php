<?php

namespace Illuminate\Contracts\Encryption;

interface Encrypter
{
    /**
     * Encrypt the given value.
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function encrypt(#[\SensitiveParameter] $value, bool $serialize = true, bool $deterministic = false): string;

    /**
     * Decrypt the given value.
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decrypt(string $payload, bool $unserialize = true): mixed;

    /**
     * Get the encryption key that the encrypter is currently using.
     */
    public function getKey(): string;

    /**
     * Get the current encryption key and all previous encryption keys.
     */
    public function getAllKeys(): array;

    /**
     * Get the previous encryption keys.
     */
    public function getPreviousKeys(): array;
}
