<?php

namespace Illuminate\Support;

use RuntimeException;
use Illuminate\Support\ConnectionFactoryInterface as ConnectionFactory;

abstract class ConnectionManager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The driver factory instance.
     *
     * @var \Illuminate\Support\ConnectionFactoryInterface
     */
    protected $factory;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The custom connection resolvers.
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * Create a new manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  \Illuminate\Support\ConnectionFactoryInterface   $factory
     * @return void
     */
    public function __construct($app, ConnectionFactory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    abstract public function getDefaultConnection();

    /**
     * Get the configuration for a connection.
     *
     * @param  string  $name
     * @return array
     */
    abstract protected function configuration($name);

    /**
     * Get a connection instance.
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function connection($name = null)
    {
        $name = $name ?: $this->getDefaultConnection();

        // If we haven't created this connection, we'll create it
        // based on the config provided in the application.
        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Make the connection instance.
     *
     * @param  string  $name
     * @return mixed
     */
    protected function makeConnection($name)
    {
        $config = $this->configuration($name);

        // First we will check by the connection name to see if an extension has been
        // registered specifically for that connection. If it has we will call the
        // Closure and pass it the config allowing it to resolve the connection.
        if (isset($this->extensions[$name])) {
            return call_user_func($this->extensions[$name], $config, $name);
        }

        $driver = $this->parseDriverName($name, $config);

        // Next we will check to see if an extension has been registered for a driver
        // and will call the Closure if so, which allows us to have a more generic
        // resolver for the drivers themselves which applies to all connections.
        if (isset($this->extensions[$driver])) {
            return call_user_func($this->extensions[$driver], $config, $name);
        }

        return $this->factory->make($driver, $config);
    }

    /**
     * Get the driver for a connection.
     *
     * @param  string  $name
     * @param  array   $config
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function parseDriverName($name, $config)
    {
        if (isset($config['driver'])) {
            return $config['driver'];
        }

        throw new RuntimeException("Unable to determine driver for connection [$name]");
    }

    /**
     * Register an extension connection resolver.
     *
     * @param  string    $name
     * @param  callable  $resolver
     * @return void
     */
    public function extend($name, callable $resolver)
    {
        $this->extensions[$name] = $resolver;
    }

    /**
     * Return all of the created connections.
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}
