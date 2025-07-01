<?php

namespace Illuminate\Encryption;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Contracts\Encryption\StringEncrypter;

class Encrypter implements EncrypterContract, StringEncrypter
{
    /**
     * The encryption key.
     */
    protected string $key;

    /**
     * The previous / legacy encryption keys.
     */
    protected array $previousKeys = [];

    /**
     * The algorithm used for encryption.
     */
    protected string $cipher;

    /**
     * The supported cipher algorithms and their properties.
     */
    private static array $supportedCiphers = [
        'aes-128-cbc' => ['size' => 16, 'aead' => false],
        'aes-256-cbc' => ['size' => 32, 'aead' => false],
        'aes-128-gcm' => ['size' => 16, 'aead' => true],
        'aes-256-gcm' => ['size' => 32, 'aead' => true],
    ];

    /**
     * Create a new encrypter instance.
     *
     * @throws \RuntimeException
     */
    public function __construct(string $key, string $cipher = 'aes-128-cbc')
    {
        if (! static::supported($key, $cipher)) {
            $ciphers = implode(', ', array_keys(self::$supportedCiphers));

            throw new \RuntimeException("Unsupported cipher or incorrect key length. Supported ciphers are: $ciphers.");
        }

        $this->key = $key;
        $this->cipher = $cipher;
    }

    /**
     * Determine if the given key and cipher combination is valid.
     */
    public static function supported(string $key, string $cipher): bool
    {
        if (! isset(self::$supportedCiphers[strtolower($cipher)])) {
            return false;
        }

        return mb_strlen($key, '8bit') === self::$supportedCiphers[strtolower($cipher)]['size'];
    }

    /**
     * Create a new encryption key for the given cipher.
     *
     * @throws \Random\RandomException
     */
    public static function generateKey(string $cipher): string
    {
        return random_bytes(self::$supportedCiphers[strtolower($cipher)]['size'] ?? 32);
    }

    /**
     * Encrypt the given value.
     *
     * @throws \Random\RandomException
     */
    public function encrypt(#[\SensitiveParameter] $value, bool $serialize = true, bool $deterministic = false): string
    {
        $data = $serialize ? serialize($value) : $value;
        $cipher_algo = strtolower($this->cipher);
        $ivLength = openssl_cipher_iv_length($cipher_algo);
        $iv = $deterministic ? substr(hash('sha256', $data.$this->key, true), 0, $ivLength) : random_bytes($ivLength);

        $value = \openssl_encrypt(
            $serialize ? serialize($value) : $value,
            strtolower($this->cipher),
            $this->key,
            0,
            $iv,
            $tag
        );

        if ($value === false) {
            throw new EncryptException('Could not encrypt the data.');
        }

        $iv = base64_encode($iv);
        $tag = base64_encode($tag ?? '');

        $mac = self::$supportedCiphers[strtolower($this->cipher)]['aead']
            ? '' // For AEAD-algorithms, the tag / MAC is returned by \openssl_encrypt...
            : $this->hash($iv, $value, $this->key);

        $json = json_encode(compact('iv', 'value', 'mac', 'tag'), JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EncryptException('Could not encrypt the data.');
        }

        return base64_encode($json);
    }

    /**
     * Encrypt a string without serialization.
     *
     * @throws EncryptException
     * @throws \Random\RandomException
     */
    public function encryptString(#[\SensitiveParameter] $value, bool $deterministic = false): string
    {
        return $this->encrypt($value, false, $deterministic);
    }

    /**
     * Decrypt the given value.
     *
     * @throws DecryptException
     */
    public function decrypt(string $payload, bool $unserialize = true): mixed
    {
        $payload = $this->getJsonPayload($payload);

        $iv = base64_decode($payload['iv']);

        $this->ensureTagIsValid(
            $tag = empty($payload['tag']) ? null : base64_decode($payload['tag'])
        );

        $foundValidMac = false;

        $decrypted = false;

        // Here we will decrypt the value. If we are able to successfully decrypt it
        // we will then unserialize it and return it out to the caller. If we are
        // unable to decrypt this value we will throw out an exception message.
        foreach ($this->getAllKeys() as $key) {
            if (
                $this->shouldValidateMac() &&
                ! ($foundValidMac = $foundValidMac || $this->validMacForKey($payload, $key))
            ) {
                continue;
            }

            $decrypted = \openssl_decrypt($payload['value'], strtolower($this->cipher), $key, 0, $iv, $tag ?? '');

            if ($decrypted !== false) {
                break;
            }
        }

        if ($this->shouldValidateMac() && ! $foundValidMac) {
            throw new DecryptException('The MAC is invalid.');
        }

        if ($decrypted === false) {
            throw new DecryptException('Could not decrypt the data.');
        }

        return $unserialize ? unserialize($decrypted) : $decrypted;
    }

    /**
     * Decrypt the given string without unserialization.
     *
     * @throws DecryptException
     */
    public function decryptString(string $payload): string
    {
        return $this->decrypt($payload, false);
    }

    /**
     * Create a MAC for the given value.
     */
    protected function hash(
        #[\SensitiveParameter] string $iv,
        #[\SensitiveParameter] mixed $value,
        #[\SensitiveParameter] string $key
    ): string {
        return hash_hmac('sha256', $iv.$value, $key);
    }

    /**
     * Get the JSON array from the given payload.
     *
     * @throws DecryptException
     */
    protected function getJsonPayload(string $payload): array
    {
        $payload = json_decode(base64_decode($payload), true);

        // If the payload is not valid JSON or does not have the proper keys set we will
        // assume it is invalid and bail out of the routine since we will not be able
        // to decrypt the given value. We'll also check the MAC for this encryption.
        if (! $this->validPayload($payload)) {
            throw new DecryptException('The payload is invalid.');
        }

        return $payload;
    }

    /**
     * Verify that the encryption payload is valid.
     */
    protected function validPayload(mixed $payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        foreach (['iv', 'value', 'mac'] as $item) {
            if (! isset($payload[$item]) || ! is_string($payload[$item])) {
                return false;
            }
        }

        if (isset($payload['tag']) && ! is_string($payload['tag'])) {
            return false;
        }

        return strlen(base64_decode($payload['iv'], true)) === openssl_cipher_iv_length(strtolower($this->cipher));
    }

    /**
     * Determine if the MAC is valid for the given payload and key.
     */
    protected function validMacForKey(#[\SensitiveParameter] array $payload, string $key): bool
    {
        return hash_equals(
            $this->hash($payload['iv'], $payload['value'], $key),
            $payload['mac']
        );
    }

    /**
     * Ensure the given tag is a valid tag given the selected cipher.
     */
    protected function ensureTagIsValid(?string $tag): void
    {
        if (self::$supportedCiphers[strtolower($this->cipher)]['aead'] && strlen($tag) !== 16) {
            throw new DecryptException('Could not decrypt the data.');
        }

        if (! self::$supportedCiphers[strtolower($this->cipher)]['aead'] && is_string($tag)) {
            throw new DecryptException('Unable to use tag because the cipher algorithm does not support AEAD.');
        }
    }

    /**
     * Determine if we should validate the MAC while decrypting.
     */
    protected function shouldValidateMac(): bool
    {
        return ! self::$supportedCiphers[strtolower($this->cipher)]['aead'];
    }

    /**
     * Get the encryption key that the encrypter is currently using.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get the current encryption key and all previous encryption keys.
     */
    public function getAllKeys(): array
    {
        return [$this->key, ...$this->previousKeys];
    }

    /**
     * Get the previous encryption keys.
     */
    public function getPreviousKeys(): array
    {
        return $this->previousKeys;
    }

    /**
     * Set the previous / legacy encryption keys that should be utilized if decryption fails.
     */
    public function previousKeys(array $keys): self
    {
        foreach ($keys as $key) {
            if (! static::supported($key, $this->cipher)) {
                $ciphers = implode(', ', array_keys(self::$supportedCiphers));

                throw new \RuntimeException(
                    "Unsupported cipher or incorrect key length. Supported ciphers are: $ciphers."
                );
            }
        }

        $this->previousKeys = $keys;

        return $this;
    }
}
