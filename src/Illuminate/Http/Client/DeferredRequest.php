<?php

namespace Illuminate\Http\Client;

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
     * The accumulated method calls to apply.
     *
     * @var array<array{method: string, parameters: array}>
     */
    protected $methodCalls = [];

    /**
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
     * Intercept method calls and store them for deferred execution.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     */
    public function __call($method, $parameters)
    {
        // Accumulate method calls to apply in order
        $this->methodCalls[] = ['method' => $method, 'parameters' => $parameters];

        // Store a closure that will create and execute the request on-demand with all accumulated calls
        $methodCalls = $this->methodCalls;
        $factory = $this->factory;
        $handler = $this->handler;

        $this->requests[$this->key] = function () use ($factory, $handler, $methodCalls) {
            $request = $factory->setHandler($handler)->async();

            foreach ($methodCalls as $call) {
                $request = $request->{$call['method']}(...$call['parameters']);
            }

            return $request;
        };

        return $this;
    }
}
