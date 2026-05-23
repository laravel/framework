<?php

namespace Illuminate\Http\Client\Promises;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use RuntimeException;

class LazyPromise implements PromiseInterface
{
    /**
     * The promise built by the creator.
     *
     * @var \GuzzleHttp\Promise\PromiseInterface
     */
    protected PromiseInterface $guzzlePromise;

    /**
     * Create a new lazy promise instance.
     *
     * @param  (\Closure(): \GuzzleHttp\Promise\PromiseInterface)  $promiseBuilder  The callback to build a new PromiseInterface.
     */
    public function __construct(protected Closure $promiseBuilder)
    {
    }

    /**
     * Build the promise from the promise builder.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function buildPromise(): PromiseInterface
    {
        if (isset($this->guzzlePromise)) {
            return $this->guzzlePromise;
        }

        $this->guzzlePromise = call_user_func($this->promiseBuilder);

        return $this->guzzlePromise;
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
    public function getState(): string
    {
        if ($this->promiseNeedsBuilt()) {
            return PromiseInterface::PENDING;
        }

        return $this->guzzlePromise->getState();
    }

    #[\Override]
    public function resolve($value): void
    {
        throw new \LogicException('Cannot resolve a lazy promise.');
    }

    #[\Override]
    public function reject($reason): void
    {
        throw new \LogicException('Cannot reject a lazy promise.');
    }

    #[\Override]
    public function cancel(): void
    {
        throw new \LogicException('Cannot cancel a lazy promise.');
    }

    #[\Override]
    public function wait(bool $unwrap = true)
    {
        if ($this->promiseNeedsBuilt()) {
            $this->buildPromise();
        }

        return $this->guzzlePromise->wait($unwrap);
    }

    /**
     * Proxy deferred promise calls through a new lazy promise.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function __call($method, $parameters)
    {
        if (! isset($this->guzzlePromise)) {
            return new static(fn () => $this->buildPromise()->$method(...$parameters));
        }

        return $this->guzzlePromise->$method(...$parameters);
    }

    /**
     * Determine if the promise has been created from the promise builder.
     *
     * @return bool
     */
    public function promiseNeedsBuilt(): bool
    {
        return ! isset($this->guzzlePromise);
    }
}
