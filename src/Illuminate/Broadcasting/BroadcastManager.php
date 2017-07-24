<?php

namespace Illuminate\Broadcasting;

use Pusher;
use Closure;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Illuminate\Broadcasting\Broadcasters\LogBroadcaster;
use Illuminate\Broadcasting\Broadcasters\NullBroadcaster;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\Broadcasters\RedisBroadcaster;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Contracts\Broadcasting\Factory as FactoryContract;

/**
 * @mixin \Illuminate\Contracts\Broadcasting\Broadcaster
 */
class BroadcastManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved broadcast drivers.
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Register the routes for handling broadcast authentication and sockets.
     *
     * @param  array|null  $attributes
     * @return void
     */
    public function routes(array $attributes = null)
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $attributes = $attributes ?: ['middleware' => ['web']];

        $this->app['router']->group($attributes, function ($router) {
            $router->post('/broadcasting/auth', BroadcastController::class.'@authenticate');
        });
    }

    /**
     * Get the socket ID for the given request.
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return string|null
     */
    public function socket($request = null)
    {
        if (! $request && ! $this->app->bound('request')) {
            return;
        }

        $request = $request ?: $this->app['request'];

        if ($request->hasHeader('X-Socket-ID')) {
            return $request->header('X-Socket-ID');
        }
    }

    /**
     * Begin broadcasting an event.
     *
     * @param  mixed|null  $event
     * @return \Illuminate\Broadcasting\PendingBroadcast|void
     */
    public function event($event = null)
    {
        return new PendingBroadcast($this->app->make('events'), $event);
    }

    /**
     * Queue the given event for broadcast.
     *
     * @param  mixed  $event
     * @return void
     */
    public function queue($event)
    {
        $connection = $event instanceof ShouldBroadcastNow ? 'sync' : null;

        if (is_null($connection) && isset($event->connection)) {
            $connection = $event->connection;
        }

        $queue = null;

        if (method_exists($event, 'broadcastQueue')) {
            $queue = $event->broadcastQueue();
        } elseif (isset($event->broadcastQueue)) {
            $queue = $event->broadcastQueue;
        } elseif (isset($event->queue)) {
            $queue = $event->queue;
        }

        $this->app->make('queue')->connection($connection)->pushOn(
            $queue, new BroadcastEvent(clone $event)
        );
    }

    /**
     * Get a driver instance.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function connection($driver = null)
    {
        return $this->driver($driver);
    }

    /**
     * Get a driver instance.
     *
     * @param  string  $name
     * @return mixed
     */
    public function driver($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->drivers[$name] = $this->get($name);
    }

    /**
     * Attempt to get the connection from the local cache.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function get($name)
    {
        return isset($this->drivers[$name]) ? $this->drivers[$name] : $this->resolve($name);
    }

    /**
     * Resolve the given store.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Broadcaster [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (! method_exists($this, $driverMethod)) {
            throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
        }

        return $this->{$driverMethod}($config);
    }

    /**
     * Call a custom driver creator.
     *
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createPusherDriver(array $config)
    {
        return new PusherBroadcaster(
            new Pusher($config['key'], $config['secret'],
            $config['app_id'], Arr::get($config, 'options', []))
        );
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createRedisDriver(array $config)
    {
        return new RedisBroadcaster(
            $this->app->make('redis'), Arr::get($config, 'connection')
        );
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createLogDriver(array $config)
    {
        return new LogBroadcaster(
            $this->app->make(LoggerInterface::class)
        );
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createNullDriver(array $config)
    {
        return new NullBroadcaster;
    }

    /**
     * Get the connection configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["broadcasting.connections.{$name}"];
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['broadcasting.default'];
    }

    /**
     * Set the default driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['broadcasting.default'] = $name;
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
        return $this->driver()->$method(...$parameters);
    }
}
