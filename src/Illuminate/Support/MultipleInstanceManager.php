<?php

namespace Illuminate\Support;

use Closure;
use InvalidArgumentException;
use RuntimeException;

abstract class MultipleInstanceManager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved instances.
     *
     * @var array
     */
    protected $instances = [];

    /**
     * The registered custom instance creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * The key name of the "driver" equivalent configuration option.
     *
     * @var string
     */
    protected $driverKey = 'driver';

    /**
     * Create a new manager instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get the default instance name.
     *
     * @return string
     */
    abstract public function getDefaultInstance();

    /**
     * Set the default instance name.
     *
     * @param  string  $name
     * @return void
     */
    abstract public function setDefaultInstance($name);

    /**
     * Get the instance specific configuration.
     *
     * @param  string  $name
     * @return array
     */
    abstract public function getInstanceConfig($name);

    /**
     * Get an instance by name.
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function instance($name = null)
    {
        $name = $name ?: $this->getDefaultInstance();

        return $this->instances[$name] = $this->get($name);
    }

    /**
     * Attempt to get an instance from the local cache.
     *
     * @param  string  $name
     * @return mixed
     */
    protected function get($name)
    {
        return $this->instances[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given instance.
     *
     * @param  string  $name
     * @return mixed
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function resolve($name)
    {
        $config = $this->getInstanceConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Instance [{$name}] is not defined.");
        }

        if (! array_key_exists($this->driverKey, $config)) {
            throw new RuntimeException("Instance [{$name}] does not specify a {$this->driverKey}.");
        }

        if (isset($this->customCreators[$config[$this->driverKey]])) {
            return $this->callCustomCreator($config);
        } else {
            $createMethod = 'create'.ucfirst($config[$this->driverKey]).ucfirst($this->driverKey);

            if (method_exists($this, $createMethod)) {
                return $this->{$createMethod}($config);
            } else {
                throw new InvalidArgumentException("Instance {$this->driverKey} [{$config[$this->driverKey]}] is not supported.");
            }
        }
    }

    /**
     * Call a custom instance creator.
     *
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config[$this->driverKey]]($this->app, $config);
    }

    /**
     * Unset the given instances.
     *
     * @param  array|string|null  $name
     * @return $this
     */
    public function forgetInstance($name = null)
    {
        $name ??= $this->getDefaultInstance();

        foreach ((array) $name as $instanceName) {
            if (isset($this->instances[$instanceName])) {
                unset($this->instances[$instanceName]);
            }
        }

        return $this;
    }

    /**
     * Disconnect the given instance and remove from local cache.
     *
     * @param  string|null  $name
     * @return void
     */
    public function purge($name = null)
    {
        $name ??= $this->getDefaultInstance();

        unset($this->instances[$name]);
    }

    /**
     * Register a custom instance creator Closure.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($name, Closure $callback)
    {
        $this->customCreators[$name] = $callback->bindTo($this, $this);

        return $this;
    }

    /**
     * Dynamically call the default instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->instance()->$method(...$parameters);
    }
}
