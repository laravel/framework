<?php

namespace Illuminate\Encryption;

use Illuminate\Support\Str;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Manager;

class EncryptionManager extends Manager implements Encrypter
{
    /**
     * Create an instance of the OpenSSL encryption Driver.
     *
     * @return \Illuminate\Encryption\OpenSSLEncrypter
     */
    public function createOpenSSLDriver()
    {
        $config = $this->app->make('config')->get('app.encryption');

        // If the key starts with "base64:", we will need to decode the key before handing
        // it off to the encrypter. Keys may be base-64 encoded for presentation and we
        // want to make sure to convert them back to the raw bytes before encrypting.
        if (Str::startsWith($key = $config['key'], 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return new OpenSSLEncrypter($key, $config['cipher']);
    }

    /**
     * Create a new encryption key for the given driver and cipher.
     *
     * @param  string  $driver
     * @param  string  $cipher
     * @return string
     *
     * @throws \Exception
     */
    public function generateKey($driver = null, $cipher = null)
    {
        return $this->driver($driver)->generateKey($cipher);
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
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['app.encryption.driver'] ?? 'openssl';
    }
}
