<?php

namespace Illuminate\Encryption;

use Illuminate\Support\Str;
use Illuminate\Support\Manager;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;

class EncryptionManager extends Manager implements Encrypter
{
    /**
     * Create an instance of the OpenSSL encryption Driver.
     *
     * @return \Illuminate\Encryption\OpenSslEncrypter
     */
    public function createOpenSslDriver()
    {
        $config = $this->app->make('config')->get('app.encryption');

        // If the key starts with "base64:", we will need to decode the key before handing
        // it off to the encrypter. Keys may be base-64 encoded for presentation and we
        // want to make sure to convert them back to the raw bytes before encrypting.
        if (Str::startsWith($key = $config['key'], 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return new OpenSslEncrypter($key, $config['cipher']);
    }

    /**
     * Create a new encryption key for the cipher.
     *
     * @param  string  $cipher
     * @return string
     *
     * @throws \Exception
     */
    public function generateKey($cipher = null)
    {
        return $this->driver()->generateKey($cipher);
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
        if (! $this->isValid()) {
            throw new EncryptException("The {$this->getDefaultDriver()} driver is invalid.");
        }

        return $this->driver()->encrypt($value, $serialize);
    }

    /**
     * Encrypt a string without serialization.
     *
     * @param  string  $value
     * @return string
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
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
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decrypt($payload, $unserialize = true)
    {
        if (! $this->isValid()) {
            throw new DecryptException("The {$this->getDefaultDriver()} driver is invalid.");
        }

        return $this->driver()->decrypt($payload, $unserialize);
    }

    /**
     * Decrypt the given string without unserialization.
     *
     * @param  string  $payload
     * @return string
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decryptString($payload)
    {
        return $this->decrypt($payload, false);
    }

    /**
     * Get the encryption key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->driver()->getKey();
    }

    /**
     * Determine whether the encrypter is valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return ! empty($this->getKey()) && $this->driver()->isValid();
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['app.encryption.driver'] ?? 'openssl';
    }
}
