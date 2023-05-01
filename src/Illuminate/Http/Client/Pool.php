<?php

namespace Illuminate\Http\Client;

use GuzzleHttp\Utils;

/**
 * @mixin \Illuminate\Http\Client\PendingRequest
 */
class Pool
{
    /**
     * The PendingRequest that is a basis for each pooled request.
     *
     * @var \Illuminate\Http\Client\PendingRequest
     */
    protected $pendingRequest;

    /**
     * The pool of requests.
     *
     * @var array
     */
    protected $pool = [];

    /**
     * Create a new requests pool.
     *
     * @param  \Illuminate\Http\Client\Factory|null  $factory
     * @param  \Illuminate\Http\Client\PendingRequest|null  $pendingRequest
     * @return void
     */
    public function __construct(Factory $factory = null, PendingRequest $pendingRequest = null)
    {
        if (! $pendingRequest) {
            $factory ??= new Factory();
            $pendingRequest = $factory->setHandler($this->getDefaultHandler())->newPendingRequest();
        }

        $this->pendingRequest = $pendingRequest;
    }

    /**
     * Create a default handler for the Factory.
     *
     * @return callable
     */
    protected function getDefaultHandler(): callable
    {
        if (method_exists(Utils::class, 'chooseHandler')) {
            return Utils::chooseHandler();
        }

        return \GuzzleHttp\choose_handler();
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
        return (clone $this->pendingRequest)->async();
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
        return $this->pool[] = $this->asyncRequest()->$method(...$parameters);
    }
}
