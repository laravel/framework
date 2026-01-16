<?php

namespace Illuminate\Encryption;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Contracts\Encryption\FileEncrypter as FileEncrypterContract;
use RuntimeException;

class FileEncrypter implements FileEncrypterContract
{
    /**
     * Magic bytes identifying encrypted files.
     */
    public const MAGIC = 'LENC';

    /**
     * Current format version.
     */
    public const VERSION = 1;

    /**
     * Cipher identifier for AES-256-GCM.
     */
    public const CIPHER_AES_256_GCM = 1;

    /**
     * Header size in bytes.
     */
    protected const HEADER_SIZE = 32;

    /**
     * Nonce size for AES-GCM (12 bytes).
     */
    protected const NONCE_SIZE = 12;

    /**
     * GCM tag size (16 bytes).
     */
    protected const TAG_SIZE = 16;

    /**
     * Default chunk size (64KB).
     */
    protected const DEFAULT_CHUNK_SIZE = 65536;

    /**
     * The encryption key.
     *
     * @var string
     */
    protected string $key;

    /**
     * The chunk size for streaming operations.
     *
     * @var int
     */
    protected int $chunkSize;

    /**
     * Previous keys for decryption fallback.
     *
     * @var array
     */
    protected array $previousKeys = [];

    /**
     * Create a new file encrypter instance.
     *
     * @param  string  $key
     * @param  int  $chunkSize
     * @return void
     *
     * @throws \RuntimeException
     */
    public function __construct(
        #[\SensitiveParameter] string $key,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE
    ) {
        if (mb_strlen($key, '8bit') !== 32) {
            throw new RuntimeException('File encryption requires a 32-byte key for AES-256-GCM.');
        }

        if ($chunkSize < 1024) {
            throw new RuntimeException('Chunk size must be at least 1024 bytes.');
        }

        $this->key = $key;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Set previous keys for decryption fallback.
     *
     * @param  array  $keys
     * @return static
     *
     * @throws \RuntimeException
     */
    public function previousKeys(array $keys): static
    {
        foreach ($keys as $key) {
            if (mb_strlen($key, '8bit') !== 32) {
                throw new RuntimeException('All keys must be 32 bytes for AES-256-GCM.');
            }
        }

        $this->previousKeys = $keys;

        return $this;
    }

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
    public function encryptFile(string $sourcePath, ?string $destinationPath = null, ?callable $progress = null): void
    {
        if (! file_exists($sourcePath)) {
            throw new EncryptException("Source file does not exist: {$sourcePath}");
        }

        if (! is_readable($sourcePath)) {
            throw new EncryptException("Source file is not readable: {$sourcePath}");
        }

        $destinationPath ??= $sourcePath.'.enc';

        $fileSize = filesize($sourcePath);
        $totalChunks = $this->calculateTotalChunks($fileSize);

        $sourceHandle = fopen($sourcePath, 'rb');

        if ($sourceHandle === false) {
            throw new EncryptException("Could not open source file: {$sourcePath}");
        }

        $destHandle = fopen($destinationPath, 'wb');

        if ($destHandle === false) {
            fclose($sourceHandle);
            throw new EncryptException("Could not open destination file: {$destinationPath}");
        }

        try {
            // Write header
            $header = $this->buildHeader($fileSize);
            fwrite($destHandle, $header);

            // Generate base nonce for this file
            $baseNonce = random_bytes(self::NONCE_SIZE);

            // Encrypt chunks
            $chunkIndex = 0;

            while (! feof($sourceHandle)) {
                $chunk = fread($sourceHandle, $this->chunkSize);

                if ($chunk === false || $chunk === '') {
                    break;
                }

                $encryptedChunk = $this->encryptChunk($chunk, $chunkIndex, $baseNonce);
                fwrite($destHandle, $encryptedChunk);

                $chunkIndex++;

                if ($progress !== null) {
                    $progress($chunkIndex, $totalChunks);
                }
            }
        } catch (\Throwable $e) {
            fclose($sourceHandle);
            fclose($destHandle);
            @unlink($destinationPath);

            if ($e instanceof EncryptException) {
                throw $e;
            }

            throw new EncryptException('Could not encrypt the file: '.$e->getMessage(), 0, $e);
        }

        fclose($sourceHandle);
        fclose($destHandle);
    }

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
    public function decryptFile(string $sourcePath, ?string $destinationPath = null, ?callable $progress = null): void
    {
        if (! file_exists($sourcePath)) {
            throw new DecryptException("Encrypted file does not exist: {$sourcePath}");
        }

        if (! is_readable($sourcePath)) {
            throw new DecryptException("Encrypted file is not readable: {$sourcePath}");
        }

        $sourceHandle = fopen($sourcePath, 'rb');

        if ($sourceHandle === false) {
            throw new DecryptException("Could not open encrypted file: {$sourcePath}");
        }

        try {
            // Read and parse header
            $headerData = fread($sourceHandle, self::HEADER_SIZE);

            if ($headerData === false || strlen($headerData) < self::HEADER_SIZE) {
                throw new DecryptException('Invalid encrypted file: header too short.');
            }

            $header = $this->parseHeader($headerData);
            $originalSize = $header['originalSize'];
            $chunkSize = $header['chunkSize'];

            // Calculate destination path if not provided
            if ($destinationPath === null) {
                $destinationPath = preg_replace('/\.enc$/', '', $sourcePath);

                if ($destinationPath === $sourcePath) {
                    throw new DecryptException('Could not determine destination path. Please provide one explicitly.');
                }
            }

            $destHandle = fopen($destinationPath, 'wb');

            if ($destHandle === false) {
                throw new DecryptException("Could not open destination file: {$destinationPath}");
            }

            $totalChunks = $this->calculateTotalChunks($originalSize, $chunkSize);
            $encryptedChunkSize = self::NONCE_SIZE + $chunkSize + self::TAG_SIZE;
            $bytesWritten = 0;
            $chunkIndex = 0;
            $decrypted = false;

            // Try decryption with current key, then fall back to previous keys
            $keysToTry = [$this->key, ...$this->previousKeys];

            foreach ($keysToTry as $keyToTry) {
                // Reset file position to after header
                fseek($sourceHandle, self::HEADER_SIZE);
                fseek($destHandle, 0);
                ftruncate($destHandle, 0);

                $bytesWritten = 0;
                $chunkIndex = 0;
                $decryptionFailed = false;

                while (! feof($sourceHandle) && $bytesWritten < $originalSize) {
                    // Calculate how much encrypted data to read for this chunk
                    $remainingBytes = $originalSize - $bytesWritten;
                    $expectedPlainSize = min($chunkSize, $remainingBytes);
                    $readSize = self::NONCE_SIZE + $expectedPlainSize + self::TAG_SIZE;

                    $encryptedChunk = fread($sourceHandle, $readSize);

                    if ($encryptedChunk === false || strlen($encryptedChunk) < self::NONCE_SIZE + self::TAG_SIZE + 1) {
                        break;
                    }

                    // Parse chunk components
                    $nonce = substr($encryptedChunk, 0, self::NONCE_SIZE);
                    $tag = substr($encryptedChunk, -self::TAG_SIZE);
                    $ciphertext = substr($encryptedChunk, self::NONCE_SIZE, -self::TAG_SIZE);

                    $plaintext = $this->decryptChunk($nonce, $ciphertext, $tag, $chunkIndex, $keyToTry);

                    if ($plaintext === false) {
                        $decryptionFailed = true;
                        break;
                    }

                    fwrite($destHandle, $plaintext);
                    $bytesWritten += strlen($plaintext);
                    $chunkIndex++;

                    if ($progress !== null) {
                        $progress($chunkIndex, $totalChunks);
                    }
                }

                if (! $decryptionFailed && $bytesWritten === $originalSize) {
                    $decrypted = true;
                    break;
                }
            }

            fclose($destHandle);

            if (! $decrypted) {
                @unlink($destinationPath);
                throw new DecryptException('Could not decrypt the file. The key may be incorrect or the file may be corrupted.');
            }
        } catch (\Throwable $e) {
            fclose($sourceHandle);

            if ($e instanceof DecryptException) {
                throw $e;
            }

            throw new DecryptException('Could not decrypt the file: '.$e->getMessage(), 0, $e);
        }

        fclose($sourceHandle);
    }

    /**
     * Get the decrypted contents of an encrypted file.
     *
     * @param  string  $path
     * @return string
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decryptedContents(string $path): string
    {
        $contents = '';

        $this->decryptedStream($path, function (string $chunk) use (&$contents) {
            $contents .= $chunk;
        });

        return $contents;
    }

    /**
     * Stream decrypted file contents through a callback.
     *
     * @param  string  $path
     * @param  callable  $callback
     * @return void
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decryptedStream(string $path, callable $callback): void
    {
        if (! file_exists($path)) {
            throw new DecryptException("Encrypted file does not exist: {$path}");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new DecryptException("Could not open encrypted file: {$path}");
        }

        try {
            // Read and parse header
            $headerData = fread($handle, self::HEADER_SIZE);

            if ($headerData === false || strlen($headerData) < self::HEADER_SIZE) {
                throw new DecryptException('Invalid encrypted file: header too short.');
            }

            $header = $this->parseHeader($headerData);
            $originalSize = $header['originalSize'];
            $chunkSize = $header['chunkSize'];

            $bytesRead = 0;
            $chunkIndex = 0;

            // Try with current key first
            $keysToTry = [$this->key, ...$this->previousKeys];
            $workingKey = null;

            // Test first chunk with each key
            $firstChunkStart = ftell($handle);

            foreach ($keysToTry as $keyToTry) {
                fseek($handle, $firstChunkStart);

                $remainingBytes = $originalSize - $bytesRead;
                $expectedPlainSize = min($chunkSize, $remainingBytes);
                $readSize = self::NONCE_SIZE + $expectedPlainSize + self::TAG_SIZE;

                $encryptedChunk = fread($handle, $readSize);

                if ($encryptedChunk === false || strlen($encryptedChunk) < self::NONCE_SIZE + self::TAG_SIZE + 1) {
                    continue;
                }

                $nonce = substr($encryptedChunk, 0, self::NONCE_SIZE);
                $tag = substr($encryptedChunk, -self::TAG_SIZE);
                $ciphertext = substr($encryptedChunk, self::NONCE_SIZE, -self::TAG_SIZE);

                $plaintext = $this->decryptChunk($nonce, $ciphertext, $tag, 0, $keyToTry);

                if ($plaintext !== false) {
                    $workingKey = $keyToTry;
                    $callback($plaintext);
                    $bytesRead += strlen($plaintext);
                    $chunkIndex = 1;
                    break;
                }
            }

            if ($workingKey === null) {
                throw new DecryptException('Could not decrypt the file. The key may be incorrect or the file may be corrupted.');
            }

            // Continue with remaining chunks
            while (! feof($handle) && $bytesRead < $originalSize) {
                $remainingBytes = $originalSize - $bytesRead;
                $expectedPlainSize = min($chunkSize, $remainingBytes);
                $readSize = self::NONCE_SIZE + $expectedPlainSize + self::TAG_SIZE;

                $encryptedChunk = fread($handle, $readSize);

                if ($encryptedChunk === false || strlen($encryptedChunk) < self::NONCE_SIZE + self::TAG_SIZE + 1) {
                    break;
                }

                $nonce = substr($encryptedChunk, 0, self::NONCE_SIZE);
                $tag = substr($encryptedChunk, -self::TAG_SIZE);
                $ciphertext = substr($encryptedChunk, self::NONCE_SIZE, -self::TAG_SIZE);

                $plaintext = $this->decryptChunk($nonce, $ciphertext, $tag, $chunkIndex, $workingKey);

                if ($plaintext === false) {
                    throw new DecryptException('Could not decrypt chunk '.$chunkIndex.'. The file may be corrupted.');
                }

                $callback($plaintext);
                $bytesRead += strlen($plaintext);
                $chunkIndex++;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Determine if a file appears to be encrypted.
     *
     * @param  string  $path
     * @return bool
     */
    public function isEncrypted(string $path): bool
    {
        if (! file_exists($path) || ! is_readable($path)) {
            return false;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $magic = fread($handle, 4);
        fclose($handle);

        return $magic === self::MAGIC;
    }

    /**
     * Get the chunk size used for encryption.
     *
     * @return int
     */
    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * Get the encryption key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get all encryption keys (current and previous).
     *
     * @return array
     */
    public function getAllKeys(): array
    {
        return [$this->key, ...$this->previousKeys];
    }

    /**
     * Build the file header.
     *
     * @param  int  $originalSize
     * @return string
     */
    protected function buildHeader(int $originalSize): string
    {
        // Magic bytes (4 bytes)
        $header = self::MAGIC;

        // Version (1 byte)
        $header .= chr(self::VERSION);

        // Cipher ID (1 byte)
        $header .= chr(self::CIPHER_AES_256_GCM);

        // Reserved (2 bytes)
        $header .= "\x00\x00";

        // Chunk size (4 bytes, big-endian)
        $header .= pack('N', $this->chunkSize);

        // Original file size (8 bytes, big-endian)
        $header .= pack('J', $originalSize);

        // Header HMAC (12 bytes, truncated SHA-256)
        $hmac = hash_hmac('sha256', $header, $this->key, true);
        $header .= substr($hmac, 0, 12);

        return $header;
    }

    /**
     * Parse and validate the file header.
     *
     * @param  string  $header
     * @return array
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    protected function parseHeader(string $header): array
    {
        if (strlen($header) !== self::HEADER_SIZE) {
            throw new DecryptException('Invalid header size.');
        }

        // Validate magic bytes
        $magic = substr($header, 0, 4);

        if ($magic !== self::MAGIC) {
            throw new DecryptException('Invalid encrypted file format.');
        }

        // Parse version
        $version = ord($header[4]);

        if ($version !== self::VERSION) {
            throw new DecryptException("Unsupported encrypted file version: {$version}");
        }

        // Parse cipher ID
        $cipherId = ord($header[5]);

        if ($cipherId !== self::CIPHER_AES_256_GCM) {
            throw new DecryptException("Unsupported cipher: {$cipherId}");
        }

        // Parse chunk size (big-endian)
        $chunkSize = unpack('N', substr($header, 8, 4))[1];

        // Parse original file size (big-endian)
        $originalSize = unpack('J', substr($header, 12, 8))[1];

        // Validate header HMAC with all available keys
        $storedHmac = substr($header, 20, 12);
        $headerData = substr($header, 0, 20);
        $validHmac = false;

        foreach ([$this->key, ...$this->previousKeys] as $key) {
            $computedHmac = substr(hash_hmac('sha256', $headerData, $key, true), 0, 12);

            if (hash_equals($computedHmac, $storedHmac)) {
                $validHmac = true;
                break;
            }
        }

        if (! $validHmac) {
            throw new DecryptException('Invalid header HMAC. The file may be corrupted or the key is incorrect.');
        }

        return [
            'version' => $version,
            'cipherId' => $cipherId,
            'chunkSize' => $chunkSize,
            'originalSize' => $originalSize,
        ];
    }

    /**
     * Encrypt a single chunk.
     *
     * @param  string  $data
     * @param  int  $chunkIndex
     * @param  string  $baseNonce
     * @return string
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    protected function encryptChunk(string $data, int $chunkIndex, string $baseNonce): string
    {
        // Generate unique nonce for this chunk by XORing base nonce with chunk index
        $nonce = $this->deriveChunkNonce($baseNonce, $chunkIndex);

        // Build AAD with chunk index to prevent reordering
        $aad = pack('N', $chunkIndex);

        $ciphertext = openssl_encrypt(
            $data,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $aad
        );

        if ($ciphertext === false) {
            throw new EncryptException('Could not encrypt chunk '.$chunkIndex);
        }

        return $nonce.$ciphertext.$tag;
    }

    /**
     * Decrypt a single chunk.
     *
     * @param  string  $nonce
     * @param  string  $ciphertext
     * @param  string  $tag
     * @param  int  $chunkIndex
     * @param  string  $key
     * @return string|false
     */
    protected function decryptChunk(string $nonce, string $ciphertext, string $tag, int $chunkIndex, string $key): string|false
    {
        // Build AAD with chunk index
        $aad = pack('N', $chunkIndex);

        return openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $aad
        );
    }

    /**
     * Derive a unique nonce for a chunk.
     *
     * @param  string  $baseNonce
     * @param  int  $chunkIndex
     * @return string
     */
    protected function deriveChunkNonce(string $baseNonce, int $chunkIndex): string
    {
        // XOR the last 4 bytes of the base nonce with the chunk index
        $nonce = $baseNonce;
        $indexBytes = pack('N', $chunkIndex);

        for ($i = 0; $i < 4; $i++) {
            $nonce[self::NONCE_SIZE - 4 + $i] = $nonce[self::NONCE_SIZE - 4 + $i] ^ $indexBytes[$i];
        }

        return $nonce;
    }

    /**
     * Calculate the total number of chunks for a file.
     *
     * @param  int  $fileSize
     * @param  int|null  $chunkSize
     * @return int
     */
    protected function calculateTotalChunks(int $fileSize, ?int $chunkSize = null): int
    {
        $chunkSize ??= $this->chunkSize;

        if ($fileSize === 0) {
            return 0;
        }

        return (int) ceil($fileSize / $chunkSize);
    }
}
