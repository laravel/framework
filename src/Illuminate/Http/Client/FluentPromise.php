<?php

namespace Illuminate\Http\Client;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * A decorated Promise which allows for chaining callbacks.
 */
class FluentPromise implements PromiseInterface
{
    use ForwardsCalls;

    /**
     * Create a new fluent promise instance.
     *
     * @param  \GuzzleHttp\Promise\PromiseInterface|(\Closure(): \GuzzleHttp\Promise\PromiseInterface)  $guzzlePromise
     */
    public function __construct(protected PromiseInterface|Closure $guzzlePromise)
    {
    }

    #[\Override]
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        return $this->__call('then', [$onFulfilled, $onRejected]);
    }

    #[\Override]
    public function otherwise(callable $onRejected): PromiseInterface
    {
        return $this->__call('otherwise', [$onRejected]);
    }

    #[\Override]
    public function resolve($value): void
    {
        $this->__call('resolve', [$value]);
    }

    #[\Override]
    public function reject($reason): void
    {
        $this->__call('reject', [$reason]);
    }

    #[\Override]
    public function cancel(): void
    {
        $this->__call('cancel', []);
    }

    #[\Override]
    public function wait(bool $unwrap = true)
    {
        return $this->__call('wait', [$unwrap]);
    }

    #[\Override]
    public function getState(): string
    {
        if (! $this->guzzlePromise instanceof PromiseInterface) {
            return PromiseInterface::PENDING;
        }

        return $this->guzzlePromise->getState();
    }

    /**
     * Get the underlying Guzzle promise.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface|(\Closure(): \GuzzleHttp\Promise\PromiseInterface)
     */
    public function getGuzzlePromise(): PromiseInterface|Closure
    {
        return $this->guzzlePromise;
    }

    /**
     * Proxy requests to the underlying promise interface and update the local promise.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (is_callable($this->guzzlePromise)) {
            $this->guzzlePromise = call_user_func($this->guzzlePromise);
        }

        $result = $this->forwardCallTo($this->guzzlePromise, $method, $parameters);

        if (! $result instanceof PromiseInterface) {
            return $result;
        }

        $this->guzzlePromise = $result;

        return $this;
    }
}
