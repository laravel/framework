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
     * The number of times to retry failed requests in the pool.
     *
     * @var array|int|null
     */
    protected $retryTimes = null;

    /**
     * The number of milliseconds to wait between retries.
     *
     * @var (Closure(int, mixed): int)|int
     */
    protected $retrySleep = 0;

    /**
     * The callback that determines if a request should be retried.
     *
     * @var (callable(\Throwable, \Illuminate\Http\Client\PendingRequest, string|null): bool)|null
     */
    protected $retryWhen = null;

    /**
     * Whether to throw an exception when all retries fail.
     *
     * @var bool
     */
    protected $retryThrow = true;

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
     * Set the retry configuration for all requests in the pool.
     *
     * @param  array|int  $times
     * @param  (Closure(int, mixed): int)|int  $sleepMilliseconds
     * @param  (callable(\Throwable, \Illuminate\Http\Client\PendingRequest, string|null): bool)|null  $when
     * @param  bool  $throw
     * @return $this
     */
    public function retry(array|int $times, Closure|int $sleepMilliseconds = 0, ?callable $when = null, bool $throw = true)
    {
        $this->retryTimes = $times;
        $this->retrySleep = $sleepMilliseconds;
        $this->retryWhen = $when;
        $this->retryThrow = $throw;

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
        $request = $this->factory->setHandler($this->handler)->async();

        if (! is_null($this->retryTimes)) {
            $request->retry(
                $this->retryTimes,
                $this->retrySleep,
                $this->retryWhen,
                $this->retryThrow,
            );
        }

        return $request;
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
