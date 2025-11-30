<?php

namespace Illuminate\Http\Client;

/**
 * Deferred request wrapper for proper concurrency control.
 *
 * This class wraps PendingRequest to defer execution until the request
 * is actually needed by the batch/pool concurrency manager.
 */
class DeferredRequest
{
    /**
     * Reference to the pool/batch requests array.
     *
     * @var array
     */
    protected $requests;

    /**
     * The key for this request in the pool/batch.
     *
     * @var string|int
     */
    protected $key;

    /**
     * The factory instance.
     *
     * @var \Illuminate\Http\Client\Factory
     */
    protected $factory;

    /**
     * The Guzzle handler.
     *
     * @var callable
     */
    protected $handler;

    /**
     * Create a new deferred request instance.
     *
     * @param  array  &$requests  Reference to the pool/batch requests array
     * @param  string|int  $key  The key for this request
     * @param  \Illuminate\Http\Client\Factory  $factory
     * @param  callable  $handler
     */
    public function __construct(array &$requests, $key, Factory $factory, callable $handler)
    {
        $this->requests = &$requests;
        $this->key = $key;
        $this->factory = $factory;
        $this->handler = $handler;
    }

    /**
     * Intercept method calls and store them as closures for deferred execution.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     */
    public function __call($method, $parameters)
    {
        // Store a closure that will create and execute the request on-demand
        $this->requests[$this->key] = fn () => $this->factory
            ->setHandler($this->handler)
            ->async()
            ->$method(...$parameters);

        return $this;
    }
}
