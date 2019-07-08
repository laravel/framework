<?php

namespace Illuminate\Encryption;

use InvalidArgumentException;
use RuntimeException;
use Illuminate\Contracts\Encryption\Factory as FactoryContract;
use Illuminate\Support\Str;

class EncryptionManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter[]
     */
    protected $encrypters = [];

    /**
     * Create a new Encryption manager instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @param string|null $name
     * @return \Illuminate\Contracts\Encryption\Encrypter
     */
    public function encrypter($name = null)
    {
        $name = $name ?: $this->getDefaultEncrypter();

        if (! isset($this->encrypter[$name])) {
            $this->encrypters[$name] = $this->makeEncrypter($name);
        }

        return $this->encrypters[$name];
    }

    /**
     * @param string $name
     * @return \Illuminate\Encryption\Encrypter
     */
    protected function makeEncrypter($name)
    {
        $config = $this->resolveConfiguration($name);

        return new Encrypter($this->getKey($config), $config['cipher'] ?? 'AES-128-CBC');
    }

    /**
     * @param array $config
     * @return string
     */
    protected function getKey($config)
    {
        $key = tap($config['key'], function ($key) {
            if (empty($key)) {
                throw new RuntimeException(
                    'No application encryption key has been specified.'
                );
            }
        });

        // If the key starts with "base64:", we will need to decode the key before handing
        // it off to the encrypter. Keys may be base-64 encoded for presentation and we
        // want to make sure to convert them back to the raw bytes before encrypting.
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return $key;
    }

    /**
     * @param string $name
     * @return array
     */
    protected function resolveConfiguration($name)
    {
        $config = $this->app['config']['encryption.encrypters'];

        if (! isset($config[$name])) {
            throw new InvalidArgumentException("Encrypter [{$name}] not configured.");
        }

        return $config[$name];
    }

    /**
     * Get the default encrypter name.
     *
     * @return string
     */
    public function getDefaultEncrypter()
    {
        return $this->app['config']['encryption.default'];
    }

    /**
     * Set the default encrypter name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultEncrypter($name)
    {
        $this->app['config']['encryption.default'] = $name;
    }

    /**
     * @return array
     */
    public function getEncrypters()
    {
        return $this->encrypters;
    }

    /**
     * Dynamically pass methods to the default encrypter.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->encrypter()->$method(...$parameters);
    }
}
