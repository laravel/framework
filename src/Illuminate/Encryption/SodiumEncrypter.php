<?php

namespace Illuminate\Encryption;

use Sodium;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class SodiumEncrypter extends BaseEncrypter implements EncrypterContract
{
    /**
     * Create a new encrypter instance.
     *
     * @param  string $key
     * @param  string $cipher
     */
    public function __construct($key, $cipher = null)
    {
        $this->key = Sodium\crypto_generichash($key);
    }

    /**
     * Encrypt the given value.
     *
     * @param  string  $value
     * @return string
     */
    public function encrypt($value)
    {
        $nonce = Sodium\randombytes_buf(Sodium\CRYPTO_SECRETBOX_NONCEBYTES);
        $nonceHex = Sodium\bin2hex($nonce);

        $encryptedString = Sodium\crypto_secretbox($value, $nonce, $this->key);
        $encryptedStringHex = Sodium\bin2hex($encryptedString);

        return sprintf('%s.%s', $nonceHex, $encryptedStringHex);
    }

    /**
     * Decrypt the given value.
     *
     * @param  string  $payload
     * @return string
     */
    public function decrypt($payload)
    {
        $payload = explode('.', $payload);

        $plaintext = Sodium\crypto_secretbox_open(
            Sodium\hex2bin($payload[1]),
            Sodium\hex2bin($payload[0]),
            $this->key
        );

        if ($plaintext === false) {
            throw new DecryptException('Could not decrypt the data.');
        }

        return $plaintext;
    }
}
