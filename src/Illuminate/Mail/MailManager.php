<?php

namespace Illuminate\Mail;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class MailManager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

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
     * Create a new mail manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  \Illuminate\Mail\TransportFactory   $factory
     * @return void
     */
    public function __construct($app, TransportFactory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;
    }

    /**
     * Get a mailer connection instance.
     *
     * @param  string|null  $name
     * @return \Illuminate\Mail\Mailer
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
     * Make the database connection instance.
     *
     * @param  string  $name
     * @return \Illuminate\Mail\Mailer
     *
     * @throws \InvalidArgumentException
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

        if (! isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

        // Next we will create the Mailer instance based on config.
        return $this->app->make('mailer', [
            $this->app->make('swift.mailer', [
                $this->makeTransport($config['driver'], $config),
            ]),
        ]);
    }

    /**
     * Get the configuration for a connection.
     *
     * @param  string  $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration($name)
    {
        // To get the mailer connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $connections = $this->app['config']['mail.connections'];

        if (is_null($config = Arr::get($connections, $name))) {
            throw new InvalidArgumentException("Mail connection [$name] not configured.");
        }

        // Here we'll blend in the service configuration if applicable.
        if ($service = Arr::get($config, 'service')) {
            $config['service'] = $this->app['config']->get('services.'.$service, []);
        }

        return $config;
    }

    /**
     * Make the Transport for a given driver and configuration.
     *
     * @param string $driver
     * @param array  $config
     * @return \Swift_Transport
     */
    protected function makeTransport($driver, $config)
    {
        // We first check if an extension has been registered for a driver and will
        // call the Closure if so, which allows us to have a more generic resolver
        // for the drivers themselves which applies to all connections.
        if (isset($this->extensions[$driver])) {
            return call_user_func($this->extensions[$driver], $config);
        }

        return $this->factory->create($driver, $config);
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->app['config']['mail.default'];
    }

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->app['config']['mail.default'] = $name;
    }

    /**
     * Get all of the support drivers.
     *
     * @return array
     */
    public function supportedDrivers()
    {
        return ['array', 'log', 'mailgun', 'mandrill', 'sendmail', 'ses', 'smtp', 'sparkpost'];
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
