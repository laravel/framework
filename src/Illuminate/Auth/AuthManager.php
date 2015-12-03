<?php

namespace Illuminate\Auth;

use Closure;
use InvalidArgumentException;
use Illuminate\Contracts\Auth\Guard as GuardContract;

class AuthManager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * The array of created "drivers".
     *
     * @var array
     */
    protected $guards = [];

    /**
     * Create a new Auth manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Attempt to get the guard from the local cache.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatelessGuard
     */
    public function driver($name = null)
    {
        return $this->guard($name);
    }

    /**
     * Attempt to get the guard from the local cache.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatelessGuard
     */
    public function guard($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return isset($this->guards[$name])
                    ? $this->guards[$name]
                    : $this->guards[$name] = $this->resolve($name);
    }

    /**
     * Resolve the given store.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($name, $config);
        } else {
            return $this->{'create'.ucfirst($config['driver']).'Driver'}($name, $config);
        }
    }

    /**
     * Call a custom driver creator.
     *
     * @param  string  $name
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator($name, array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $name, $config);
    }

    /**
     * Create a session based authentication guard.
     *
     * @param  string  $name
     * @param  array  $config
     * @return \Illuminate\Auth\Guard
     */
    public function createSessionDriver($name, $config)
    {
        $provider = $this->createUserProvider($config['provider']);

        $guard = new Guard($name, $provider, $this->app['session.store']);

        // When using the remember me functionality of the authentication services we
        // will need to be set the encryption instance of the guard, which allows
        // secure, encrypted cookie values to get generated for those cookies.
        if (method_exists($guard, 'setCookieJar')) {
            $guard->setCookieJar($this->app['cookie']);
        }

        if (method_exists($guard, 'setDispatcher')) {
            $guard->setDispatcher($this->app['events']);
        }

        if (method_exists($guard, 'setRequest')) {
            $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
        }

        return $guard;
    }

    /**
     * Create the user provider implementation for the driver.
     *
     * @param  string  $provider
     * @return \Illuminate\Contracts\Auth\UserProvider
     */
    protected function createUserProvider($provider)
    {
        $config = $this->app['config']['auth.providers.'.$provider];

        switch ($config['driver']) {
            case 'database':
                return $this->createDatabaseProvider($config);
            case 'eloquent':
                return $this->createEloquentProvider($config);
            default:
                throw new InvalidArgumentException("Authentication user provider [{$config['driver']}] is not defined.");
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
     * Get the cache connection configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["auth.guards.{$name}"];
    }

    /**
     * Get the default authentication driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['auth.driver'];
    }

    /**
     * Set the default authentication driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['auth.driver'] = $name;
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string    $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->guard(), $method], $parameters);
    }
}
