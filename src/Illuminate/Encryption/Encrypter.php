<?php

namespace Illuminate\Encryption;

use RuntimeException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class Encrypter implements EncrypterContract
{
    /**
     * The supported cipher algorithms and their settings.
     *
     * @var array
     */
    private static $supportedCiphers = [
        'aes-128-cbc' => ['size' => 16, 'aead' => false],
        'aes-256-cbc' => ['size' => 32, 'aead' => false],
        'aes-128-gcm' => ['size' => 16, 'aead' => true, 'since' => '7.1.0'],
        'aes-256-gcm' => ['size' => 32, 'aead' => true, 'since' => '7.1.0'],
    ];

    /**
     * The encryption key.
     *
     * @var string
     */
    protected $key;

    /**
     * The algorithm used for encryption.
     *
     * @var string
     */
    protected $cipher;

    /**
     * Whether the cipher is AEAD cipher.
     *
     * @var bool
     */
    protected $aead;

    /**
     * Create a new encrypter instance.
     *
     * @param  string  $key
     * @param  string  $cipher
     * @return void
     *
     * @throws \RuntimeException
     */
    public function __construct($key, $cipher = 'AES-128-CBC')
    {
        $key = (string) $key;
        $cipher = strtolower($cipher);

        if (static::supported($key, $cipher)) {
            $this->key = $key;
            $this->cipher = $cipher;
            $this->aead = self::$supportedCiphers[$this->cipher]['aead'];
        } else {
            $ciphers = implode(', ', $this->getAvailableCiphers());
            throw new RuntimeException("The only supported ciphers are $ciphers with the correct key lengths.");
        }
    }

    /**
     * Determine if the given key and cipher combination is valid.
     *
     * @param  string  $key
     * @param  string  $cipher
     * @return bool
     */
    public static function supported($key, $cipher)
    {
        if (! isset(self::$supportedCiphers[$cipher])) {
            return false;
        }

        $cipherSetting = self::$supportedCiphers[$cipher];
        if (isset($cipherSetting['since']) &&
            version_compare(PHP_VERSION, $cipherSetting['since'], '<')
        ) {
            return false;
        }

        return mb_strlen($key, '8bit') === $cipherSetting['size'];
    }

    /**
     * Create a new encryption key for the given cipher.
     *
     * @param  string  $cipher
     * @return string
     */
    public static function generateKey($cipher)
    {
        return random_bytes(self::$supportedCiphers[$cipher]['size'] ?? 32);
    }

    /**
     * Encrypt the given value.
     *
     * @param  mixed  $value
     * @param  bool  $serialize
     * @return string
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function encrypt($value, $serialize = true)
    {
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));

        if ($serialize) {
            $value = serialize($value);
        }
        $json = json_encode(
            $this->aead ? $this->encryptAead($value, $iv) : $this->encryptMac($value, $iv)
        );

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EncryptException('Could not encrypt the data.');
        }

        return base64_encode($json);
    }

    /**
     * Encrypt value using AEAD cipher.
     *
     * @param string $value
     * @param string $iv
     * @return array
     */
    protected function encryptAead($value, $iv)
    {
        // We will encrypt AEAD ciphers which will give us authentication tag.
        $value = openssl_encrypt($value, $this->cipher, $this->key, 0, $iv, $tag);

        if ($value === false) {
            throw new EncryptException('Could not encrypt the data.');
        }

        return [
            'iv' => base64_encode($iv),
            'value' => $value,
            'tag' => base64_encode($tag),
        ];
    }

    /**
     * Encrypt value using non AEAD cipher and MAC.
     *
     * @param string $value
     * @param string $iv
     * @return array
     */
    protected function encryptMac($value, $iv)
    {
        // First we will encrypt the value using OpenSSL. After this is encrypted we
        // will proceed to calculating a MAC for the encrypted value so that this
        // value can be verified later as not having been changed by the users.
        $value = openssl_encrypt($value, $this->cipher, $this->key, 0, $iv);

        if ($value === false) {
            throw new EncryptException('Could not encrypt the data.');
        }

        // Once we get the encrypted value we'll go ahead and base64_encode the input
        // vector and create the MAC for the encrypted value so we can then verify
        // its authenticity. Then, we'll JSON the data into the "payload" array.
        $mac = $this->hash($iv = base64_encode($iv), $value);

        return compact('iv', 'value', 'mac');
    }

    /**
     * Encrypt a string without serialization.
     *
     * @param  string  $value
     * @return string
     */
    public function encryptString($value)
    {
        return $this->encrypt($value, false);
    }

    /**
     * Decrypt the given value.
     *
     * @param  mixed  $payload
     * @param  bool  $unserialize
     * @return string
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decrypt($payload, $unserialize = true)
    {
        $payload = $this->getJsonPayload($payload);

        $iv = base64_decode($payload['iv']);
        $decrypted = $this->aead
            ? $this->decryptAead($payload, $iv)
            : $this->decryptMac($payload, $iv);

        return $unserialize ? unserialize($decrypted) : $decrypted;
    }

    /**
     * Decrypt value using AEAD cipher.
     *
     * @param array $payload
     * @param string $iv
     * @return string
     */
    protected function decryptAead($payload, $iv)
    {
        // Here we will decrypt the value. If we are able to successfully decrypt it
        // we will return it. If we are nable to decrypt this value, it means that the tag
        // is invalid and we will throw out an exception message.
        $decrypted = openssl_decrypt(
            $payload['value'], $this->cipher, $this->key, 0, $iv, base64_decode($payload['tag'])
        );

        if ($decrypted === false) {
            throw new DecryptException('The authentication tag is invalid.');
        }

        return $decrypted;
    }

    /**
     * Decrypt value using non AEAD cipher and MAC.
     *
     * @param array $payload
     * @param string $iv
     * @return string
     */
    protected function decryptMac($payload, $iv)
    {
        // First we will check if the MAC is valid
        if (! $this->validMac($payload)) {
            throw new DecryptException('The MAC is invalid.');
        }

        // Here we will decrypt the value. If we are able to successfully decrypt it
        // we will return it. If we are unable to decrypt this value we will throw out
        // an exception message (this should however never happen for AES CBC mode).
        $decrypted = openssl_decrypt(
            $payload['value'], $this->cipher, $this->key, 0, $iv
        );

        if ($decrypted === false) {
            throw new DecryptException('Could not decrypt the data.');
        }

        return $decrypted;
    }

    /**
     * Decrypt the given string without unserialization.
     *
     * @param  string  $payload
     * @return string
     */
    public function decryptString($payload)
    {
        return $this->decrypt($payload, false);
    }

    /**
     * Create a MAC for the given value.
     *
     * @param  string  $iv
     * @param  mixed  $value
     * @return string
     */
    protected function hash($iv, $value)
    {
        return hash_hmac('sha256', $iv.$value, $this->key);
    }

    /**
     * Get the JSON array from the given payload.
     *
     * @param  string  $payload
     * @return array
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    protected function getJsonPayload($payload)
    {
        $payload = json_decode(base64_decode($payload), true);

        // If the payload is not valid JSON or does not have the proper keys set we will
        // assume it is invalid and bail out of the routine since we will not be able
        // to decrypt the given value.
        if (! $this->validPayload($payload)) {
            throw new DecryptException('The payload is invalid.');
        }

        return $payload;
    }

    /**
     * Verify that the encryption payload is valid.
     *
     * @param  mixed  $payload
     * @return bool
     */
    protected function validPayload($payload)
    {
        if (! is_array($payload)) {
            return false;
        }

        return $this->aead
            ? isset($payload['iv'], $payload['value'], $payload['tag'])
            : isset($payload['iv'], $payload['value'], $payload['mac']);
    }

    /**
     * Determine if the MAC for the given payload is valid.
     *
     * @param  array  $payload
     * @return bool
     */
    protected function validMac(array $payload)
    {
        $calculated = $this->calculateMac($payload, $bytes = random_bytes(16));

        return hash_equals(
            hash_hmac('sha256', $payload['mac'], $bytes, true), $calculated
        );
    }

    /**
     * Calculate the hash of the given payload.
     *
     * @param  array  $payload
     * @param  string  $bytes
     * @return string
     */
    protected function calculateMac($payload, $bytes)
    {
        return hash_hmac(
            'sha256', $this->hash($payload['iv'], $payload['value']), $bytes, true
        );
    }

    /**
     * Get available ciphers.
     *
     * @return array
     */
    private function getAvailableCiphers()
    {
        $availableCiphers = [];
        foreach (self::$supportedCiphers as $cipherName => $setting) {
            if (! isset($setting['since']) ||
                version_compare(PHP_VERSION, $setting['since'], '>=')
            ) {
                $availableCiphers[] = strtoupper($cipherName);
            }
        }

        return $availableCiphers;
    }

    /**
     * Get the encryption key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
}
