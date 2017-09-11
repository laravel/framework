<?php

namespace Illuminate\Redis\Connections;

use Closure;
use Illuminate\Redis\Limiters\DurationLimiterBuilder;
use Illuminate\Redis\Limiters\ConcurrencyLimiterBuilder;

/**
 * @mixin \Predis\Client
 */
abstract class Connection
{
    /**
     * The Predis client.
     *
     * @var \Predis\Client
     */
    protected $client;

    /**
     * Subscribe to a set of given channels for messages.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @param  string  $method
     * @return void
     */
    abstract public function createSubscription($channels, Closure $callback, $method = 'subscribe');

    /**
     * Funnel a callback for a maximum number of simultaneous executions.
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Limiters\ConcurrencyLimiterBuilder
     */
    public function funnel($name)
    {
        return new ConcurrencyLimiterBuilder($this, $name);
    }

    /**
     * Throttle a callback for a maximum number of executions over a given duration.
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Limiters\DurationLimiterBuilder
     */
    public function throttle($name)
    {
        return new DurationLimiterBuilder($this, $name);
    }

    /**
     * Get the underlying Redis client.
     *
     * @return mixed
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * Subscribe to a set of given channels for messages.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function subscribe($channels, Closure $callback)
    {
        return $this->createSubscription($channels, $callback, __FUNCTION__);
    }

    /**
     * Subscribe to a set of given channels with wildcards.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function psubscribe($channels, Closure $callback)
    {
        return $this->createSubscription($channels, $callback, __FUNCTION__);
    }

    /**
     * Run a command against the Redis database.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function command($method, array $parameters = [])
    {
        return $this->client->{$method}(...$parameters);
    }

    /**
     * Pass other method calls down to the underlying client.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->command($method, $parameters);
    }
}
