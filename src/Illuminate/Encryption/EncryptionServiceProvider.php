<?php

namespace Illuminate\Encryption;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use Illuminate\Support\Facades\Log;
use Illuminate\Encryption\MissingAppKeyException;
use InvalidArgumentException;

class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerEncrypter();
        $this->registerSerializableClosureSecurityKey();
    }

    /**
     * Register the encrypter.
     *
     * @return void
     */
    protected function registerEncrypter()
    {
        $this->app->singleton('encrypter', function ($app) {
            $config = $app->make('config')->get('app');

            try {
                return (new Encrypter($this->parseKey($config), $config['cipher']))
                    ->previousKeys(array_map(
                        fn($key) => $this->parseKey(['key' => $key]),
                        $config['previous_keys'] ?? []
                    ));
            } catch (InvalidArgumentException $e) {
                Log::critical('Failed to create encrypter: ' . $e->getMessage());
                throw $e; // Lança a exceção após o log
            }
        });
    }

    /**
     * Configure Serializable Closure signing for security.
     *
     * @return void
     */
    protected function registerSerializableClosureSecurityKey()
    {
        $config = $this->app->make('config')->get('app');

        if (!class_exists(SerializableClosure::class) || empty($config['key'])) {
            return;
        }

        try {
            SerializableClosure::setSecretKey($this->parseKey($config));
        } catch (InvalidArgumentException $e) {
            Log::critical('Failed to set SerializableClosure secret key: ' . $e->getMessage());
            throw $e; 
        }
    }

    /**
     * Parse the encryption key.
     *
     * @param  array  $config
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function parseKey(array $config)
    {
        $key = $this->key($config);

        if (Str::startsWith($key, $prefix = 'base64:')) {
            $decodedKey = base64_decode(Str::after($key, $prefix), true);
            if ($decodedKey === false) {
                Log::error('Base64 decoding failed for key: ' . $key);
                throw new InvalidArgumentException('Invalid base64 encoding for the encryption key.');
            }
            return $decodedKey;
        }

        return $key;
    }

    /**
     * Extract the encryption key from the given configuration.
     *
     * @param  array  $config
     * @return string
     *
     * @throws \Illuminate\Encryption\MissingAppKeyException
     */
    protected function key(array $config)
    {
        return tap($config['key'], function ($key) {
            if (empty($key)) {
                Log::critical('Application key is missing.');
                throw new MissingAppKeyException('The application key is required.');
            }
        });
    }
}
