<?php

namespace Illuminate\Encryption;

use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;

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
        $this->registerFileEncrypter();
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

            return (new Encrypter($this->parseKey($config), $config['cipher']))
                ->previousKeys(array_map(
                    fn ($key) => $this->parseKey(['key' => $key]),
                    $config['previous_keys'] ?? []
                ));
        });
    }

    /**
     * Register the file encrypter.
     *
     * @return void
     */
    protected function registerFileEncrypter()
    {
        $this->app->singleton('file.encrypter', function ($app) {
            $key = Env::get('FILE_ENCRYPTION_KEY');

            if (empty($key)) {
                throw new MissingAppKeyException('No FILE_ENCRYPTION_KEY has been specified.');
            }

            $previousKeys = Env::get('FILE_ENCRYPTION_PREVIOUS_KEYS', '');

            $previousKeysArray = array_filter(
                explode(',', $previousKeys),
                fn ($key) => ! empty($key)
            );

            return (new FileEncrypter($this->parseKey(['key' => $key])))
                ->previousKeys(array_map(
                    fn ($key) => $this->parseKey(['key' => trim($key)]),
                    $previousKeysArray
                ));
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

        if (! class_exists(SerializableClosure::class) || empty($config['key'])) {
            return;
        }

        SerializableClosure::setSecretKey($this->parseKey($config));
    }

    /**
     * Parse the encryption key.
     *
     * @param  array  $config
     * @return string
     */
    protected function parseKey(array $config)
    {
        if (Str::startsWith($key = $this->key($config), $prefix = 'base64:')) {
            $key = base64_decode(Str::after($key, $prefix));
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
                throw new MissingAppKeyException;
            }
        });
    }
}
