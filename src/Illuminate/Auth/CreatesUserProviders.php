<?php

namespace Illuminate\Auth;

use Illuminate\Support\Arr;
use InvalidArgumentException;

trait CreatesUserProviders
{
    /**
     * The registered custom provider creators.
     *
     * @var array
     */
    protected $customProviderCreators = [];

    /**
     * Create the user provider implementation for the driver.
     *
     * @param  string|null  $provider
     * @return \Illuminate\Contracts\Auth\UserProvider
     *
     * @throws \InvalidArgumentException
     */
    public function createUserProvider($provider = null)
    {
        $provider = $provider ?: $this->getDefaultUserProvider();

        $config = $this->app['config']['auth.providers.'.$provider];

        if (isset($this->customProviderCreators[$driver = Arr::get($config, 'driver')])) {
            return call_user_func(
                $this->customProviderCreators[$driver], $this->app, $config
            );
        }

        switch ($driver) {
            case 'database':
                return $this->createDatabaseProvider($config);
            case 'eloquent':
                return $this->createEloquentProvider($config);
            default:
                throw new InvalidArgumentException("Authentication user provider [{$driver}] is not defined.");
        }
    }

    /**
     * Create an instance of the database user provider.
     *
     * @param  array  $config
     * @return \Illuminate\Auth\DatabaseUserProvider
     */
    protected function createDatabaseProvider($config)
    {
        $connection = $this->app['db']->connection();

        return new DatabaseUserProvider($connection, $this->app['hash'], $config['table']);
    }

    /**
     * Create an instance of the Eloquent user provider.
     *
     * @param  array  $config
     * @return \Illuminate\Auth\EloquentUserProvider
     */
    protected function createEloquentProvider($config)
    {
        return new EloquentUserProvider($this->app['hash'], $config['model']);
    }

    /**
     * Get the default user provider name.
     *
     * @return string
     */
    public function getDefaultUserProvider()
    {
        return $this->app['config']['auth.defaults.provider'];
    }
}
