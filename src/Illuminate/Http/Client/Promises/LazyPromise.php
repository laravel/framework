<?php

namespace Illuminate\Http\Client\Promises;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use RuntimeException;

class LazyPromise implements PromiseInterface
{
    /**
     * The callbacks to execute after the Guzzle Promise has been built.
     *
     * @var list<callable>
     */
    protected array $pending = [];

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
     *
     * @throws \RuntimeException If the promise has already been built
     */
    public function buildPromise(): PromiseInterface
    {
        if (! $this->promiseNeedsBuilt()) {
            throw new RuntimeException('Promise already built');
        }

        $this->guzzlePromise = call_user_func($this->promiseBuilder);

        foreach ($this->pending as $pendingCallback) {
            $pendingCallback($this->guzzlePromise);
        }

        $this->pending = [];

        return $this->guzzlePromise;
    }

    /**
     * Append fulfillment and rejection handlers to the promise.
     *
     * @param  callable|null  $onFulfilled
     * @param  callable|null  $onRejected
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    #[\Override]
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        if ($this->promiseNeedsBuilt()) {
            $this->pending[] = static fn (PromiseInterface $promise) => $promise->then($onFulfilled, $onRejected);

            return $this;
        }

        return $this->guzzlePromise->then($onFulfilled, $onRejected);
    }

    /**
     * Append a rejection handler callback to the promise.
     *
     * @param  callable  $onRejected
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    #[\Override]
    public function otherwise(callable $onRejected): PromiseInterface
    {
        if ($this->promiseNeedsBuilt()) {
            $this->pending[] = static fn (PromiseInterface $promise) => $promise->otherwise($onRejected);

            return $this;
        }

        return $this->guzzlePromise->otherwise($onRejected);
    }

    /**
     * Get the state of the promise.
     *
     * @return string
     */
    #[\Override]
    public function getState(): string
    {
        if ($this->promiseNeedsBuilt()) {
            return PromiseInterface::PENDING;
        }

        return $this->guzzlePromise->getState();
    }

    /**
     * Resolve the promise with the given value.
     *
     * @param  mixed  $value
     * @return void
     *
     * @throws \LogicException
     */
    #[\Override]
    public function resolve($value): void
    {
        throw new \LogicException('Cannot resolve a lazy promise.');
    }

    /**
     * Reject the promise with the given reason.
     *
     * @param  mixed  $reason
     * @return void
     *
     * @throws \LogicException
     */
    #[\Override]
    public function reject($reason): void
    {
        throw new \LogicException('Cannot reject a lazy promise.');
    }

    /**
     * Cancel the promise.
     *
     * @return void
     *
     * @throws \LogicException
     */
    #[\Override]
    public function cancel(): void
    {
        throw new \LogicException('Cannot cancel a lazy promise.');
    }

    /**
     * Wait until the promise completes.
     *
     * @param  bool  $unwrap
     * @return mixed
     */
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
