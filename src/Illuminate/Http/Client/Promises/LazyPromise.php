<?php

namespace Illuminate\Http\Client\Promises;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;

class LazyPromise implements PromiseInterface
{
    /**
     * The promise built by the creator.
     *
     * @var \GuzzleHttp\Promise\PromiseInterface
     */
    protected PromiseInterface $guzzlePromise;

    /**
     * Optional callback invoked with each new promise produced by chaining.
     *
     * @var (\Closure(\Illuminate\Http\Client\Promises\LazyPromise): void)|null
     */
    protected ?Closure $chainCallback = null;

    /**
     * Create a new lazy promise instance.
     *
     * @param  (\Closure(): \GuzzleHttp\Promise\PromiseInterface)  $promiseBuilder  The callback to build a new PromiseInterface.
     */
    public function __construct(protected Closure $promiseBuilder)
    {
    }

    /**
     * Register a callback to be notified of chained promises produced from this instance.
     *
     * Used by PendingRequest so that fluent chains in pool builders are tracked.
     *
     * @param  (\Closure(\Illuminate\Http\Client\Promises\LazyPromise): void)|null  $callback
     * @return $this
     */
    public function onChain(?Closure $callback): static
    {
        $this->chainCallback = $callback;

        return $this;
    }

    /**
     * Build the promise from the promise builder, or return the already-built promise.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function buildPromise(): PromiseInterface
    {
        if (! $this->promiseNeedsBuilt()) {
            return $this->guzzlePromise;
        }

        return $this->guzzlePromise = call_user_func($this->promiseBuilder);
    }

    #[\Override]
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        return $this->chain(fn () => $this->buildPromise()->then($onFulfilled, $onRejected));
    }

    #[\Override]
    public function otherwise(callable $onRejected): PromiseInterface
    {
        return $this->chain(fn () => $this->buildPromise()->otherwise($onRejected));
    }

    /**
     * Produce a new chained LazyPromise that propagates the chain callback.
     *
     * @param  \Closure  $builder
     * @return static
     */
    protected function chain(Closure $builder): static
    {
        $chained = new static($builder);
        $chained->chainCallback = $this->chainCallback;

        if ($this->chainCallback !== null) {
            ($this->chainCallback)($chained);
        }

        return $chained;
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
     * Determine if the promise has been created from the promise builder.
     *
     * @return bool
     */
    public function promiseNeedsBuilt(): bool
    {
        return ! isset($this->guzzlePromise);
    }
}
