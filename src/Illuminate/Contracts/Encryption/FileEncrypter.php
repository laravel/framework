<?php

namespace Illuminate\Contracts\Encryption;

interface FileEncrypter
{
    /**
     * Encrypt a file to a destination path.
     *
     * @param  string  $sourcePath
     * @param  string|null  $destinationPath
     * @param  callable|null  $progress
     * @return void
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function encryptFile(string $sourcePath, ?string $destinationPath = null, ?callable $progress = null): void;

    /**
     * Decrypt a file to a destination path.
     *
     * @param  string  $sourcePath
     * @param  string|null  $destinationPath
     * @param  callable|null  $progress
     * @return void
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decryptFile(string $sourcePath, ?string $destinationPath = null, ?callable $progress = null): void;

    /**
     * Get the decrypted contents of an encrypted file.
     *
     * @param  string  $path
     * @return string
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decryptedContents(string $path): string;

    /**
     * Stream decrypted file contents through a callback.
     *
     * @param  string  $path
     * @param  callable  $callback
     * @return void
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decryptedStream(string $path, callable $callback): void;

    /**
     * Determine if a file appears to be encrypted.
     *
     * @param  string  $path
     * @return bool
     */
    public function isEncrypted(string $path): bool;

    /**
     * Get the chunk size used for encryption.
     *
     * @return int
     */
    public function getChunkSize(): int;

    /**
     * Get the encryption key.
     *
     * @return string
     */
    public function getKey(): string;
}
