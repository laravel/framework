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
     * The callback to run after a request from the pool succeeds.
     *
     * @var \Closure|null
     */
    protected $progressCallback = null;

    /**
     * The callback to run after a request from the pool fails.
     *
     * @var \Closure|null
     */
    protected $catchCallback = null;

    /**
     * The callback to run if all the requests from the pool succeeded.
     *
     * @var \Closure|null
     */
    protected $thenCallback = null;

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
        return $this->factory->setHandler($this->handler)->async();
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
     * Add a request to the pool with a numeric index.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\Http\Client\PendingRequest|\GuzzleHttp\Promise\Promise
     */
    public function __call($method, $parameters)
    {
        return $this->pool[] = $this->asyncRequest()->$method(...$parameters);
    }

    /**
     * Register a callback to run after a request from the pool succeeds.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function progress(Closure $callback)
    {
        $this->progressCallback = $callback;
    }

    /**
     * Retrieve the progress callback in the pool.
     *
     * @return \Closure|null
     */
    public function progressCallback(): ?Closure
    {
        return $this->progressCallback;
    }

    /**
     * Register a callback to run after a request from the pool fails.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function catch(Closure $callback)
    {
        $this->catchCallback = $callback;
    }

    /**
     * Retrieve the catch callback in the pool.
     *
     * @return \Closure|null
     */
    public function catchCallback(): ?Closure
    {
        return $this->catchCallback;
    }

    /**
     * Register a callback to run after all the requests from the pool succeed.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function then(Closure $callback)
    {
        $this->thenCallback = $callback;
    }

    /**
     * Retrieve the then callback in the pool.
     *
     * @return \Closure|null
     */
    public function thenCallback(): ?Closure
    {
        return $this->thenCallback;
    }
}
