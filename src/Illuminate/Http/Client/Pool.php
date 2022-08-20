<?php

namespace Illuminate\Http\Client;

use GuzzleHttp\Utils;

/**
 * @mixin \Illuminate\Http\Client\PendingRequest
 */
class Pool
{
    /**
     * The factory instance.
     *
     * @var \Illuminate\Http\Client\PendingRequest
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
     * @var array
     */
    protected $pool = [];

    /**
     * Create a new requests pool.
     *
     * @param  \Illuminate\Http\Client\PendingRequest|null  $factory
     * @return void
     */
    public function __construct(PendingRequest $factory = null)
    {
        if (method_exists(Utils::class, 'chooseHandler')) {
            $handler = Utils::chooseHandler();
        } else {
            $handler = \GuzzleHttp\choose_handler();
        }

        $this->factory = ($factory ?? new Factory())->async()->setHandler($handler);
    }

    /**
     * Add a request to the pool with a key.
     *
     * @param  string  $key
     * @return \Illuminate\Http\Client\PendingRequest
     */
    public function as(string $key)
    {
        return $this->pool[$key] = $this->newPendingAsyncRequest();
    }

    /**
     * Retrieve a new async pending request.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function newPendingAsyncRequest()
    {
        return clone $this->factory;
    }

    /**
     * Retrieve the requests in the pool.
     *
     * @return array
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
     * @return \Illuminate\Http\Client\PendingRequest
     */
    public function __call($method, $parameters)
    {
        return $this->pool[] = $this->newPendingAsyncRequest()->$method(...$parameters);
    }
}
