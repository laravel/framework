<?php

namespace Illuminate\Http\Client;

use Closure;
use GuzzleHttp\Utils;

/**
 * @mixin \Illuminate\Http\Client\Factory
 */
class Pool
{
    /**
     * The factory instance.
     *
     * @var \Illuminate\Http\Client\Factory
     */
    protected $factory;

    /**
     * The handler function for the Guzzle client.
     *
     * @var callable
     */
    protected $handler;

    /**
     * The pool of requests.
     *
     * @var array<array-key, \Illuminate\Http\Client\PendingRequest>
     */
    protected $pool = [];

    /**
     * The callback to configure default settings for all requests in the pool.
     *
     * @var (Closure(\Illuminate\Http\Client\PendingRequest): \Illuminate\Http\Client\PendingRequest)|null
     */
    protected $defaultsCallback = null;

    /**
     * Create a new requests pool.
     *
     * @param  \Illuminate\Http\Client\Factory|null  $factory
     */
    public function __construct(?Factory $factory = null)
    {
        $this->factory = $factory ?: new Factory();
        $this->handler = Utils::chooseHandler();
    }

    /**
     * Set default request configuration for all requests in the pool.
     *
     * The callback receives a PendingRequest instance and should return it
     * after applying any desired configuration
     *
     * @param  Closure(\Illuminate\Http\Client\PendingRequest): \Illuminate\Http\Client\PendingRequest  $callback
     * @return $this
     */
    public function defaults(Closure $callback)
    {
        $this->defaultsCallback = $callback;

        return $this;
    }

    /**
     * Add a request to the pool with a numeric index.
     *
     * @return \Illuminate\Http\Client\PendingRequest|\GuzzleHttp\Promise\Promise
     */
    public function newRequest()
    {
        return $this->pool[] = $this->asyncRequest();
    }

    /**
     * Add a request to the pool with a key.
     *
     * @param  string  $key
     * @return \Illuminate\Http\Client\PendingRequest
     */
    public function as(string $key)
    {
        return $this->pool[$key] = $this->asyncRequest();
    }

    /**
     * Retrieve a new async pending request.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function asyncRequest()
    {
        $request = $this->factory->createPendingRequest();

        if ($this->defaultsCallback) {
            $request = ($this->defaultsCallback)($request);
        }

        return $request->setHandler($this->handler)->async();
    }

    /**
     * Retrieve the requests in the pool.
     *
     * @return array<array-key, \Illuminate\Http\Client\PendingRequest>
     */
    public function getRequests()
    {
        return $this->pool;
    }

    /**
     * Add a request to the pool with a numeric index and forward the method call to the request.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\Http\Client\PendingRequest|\GuzzleHttp\Promise\Promise
     */
    public function __call($method, $parameters)
    {
        return $this->newRequest()->{$method}(...$parameters);
    }
}
