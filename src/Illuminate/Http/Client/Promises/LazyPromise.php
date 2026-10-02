<?php

namespace Illuminate\Http\Client\Promises;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use RuntimeException;

class LazyPromise implements PromiseInterface
{
    /**
     * The callbacks to execute with the Guzzle Promise once it has been built.
     *
     * @var list<(callable(\GuzzleHttp\Promise\PromiseInterface): void)>
     */
    protected array $pending = [];

    /**
     * The promise built by the creator.
     *
     * @var \GuzzleHttp\Promise\PromiseInterface
     */
    protected PromiseInterface $guzzlePromise;

    /**
     * The promise this promise was derived from via "then" or "otherwise".
     *
     * @var static
     */
    protected LazyPromise $root;

    /**
     * The most recently derived promise in this promise's family of chains.
     *
     * @var static
     */
    protected LazyPromise $tip;

    /**
     * Create a new lazy promise instance.
     *
     * @param  (\Closure(): \GuzzleHttp\Promise\PromiseInterface)  $promiseBuilder  The callback to build a new PromiseInterface.
     * @param  static|null  $root  The promise this promise derives from.
     */
    public function __construct(protected Closure $promiseBuilder, ?self $root = null)
    {
        $this->root = $root ?? $this;
        $this->tip = $this;
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

        $promise = call_user_func($this->promiseBuilder);

        // Building a derived promise builds the promise it chains from, which
        // resolves this promise while the builder runs, so the promise may
        // already be resolved by the time the builder returns a value...
        if ($this->promiseNeedsBuilt()) {
            $this->resolveWith($promise);
        }

        // The promise of the most recently chained handler is returned so that
        // pools and batches settle with the value of the entire chain...
        return $this->root->tip->guzzlePromise;
    }

    /**
     * Resolve the lazy promise with the given built promise.
     *
     * @param  \GuzzleHttp\Promise\PromiseInterface  $promise
     * @return void
     */
    protected function resolveWith(PromiseInterface $promise): void
    {
        $this->guzzlePromise = $promise;

        foreach ($this->pending as $pendingCallback) {
            $pendingCallback($promise);
        }

        $this->pending = [];
    }

    #[\Override]
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        if (! $this->promiseNeedsBuilt()) {
            return $this->guzzlePromise->then($onFulfilled, $onRejected);
        }

        $derived = new static(fn () => $this->buildPromise(), $this->root);

        $this->pending[] = static fn (PromiseInterface $promise) => $derived->resolveWith(
            $promise->then($onFulfilled, $onRejected)
        );

        return $this->root->tip = $derived;
    }

    #[\Override]
    public function otherwise(callable $onRejected): PromiseInterface
    {
        return $this->then(null, $onRejected);
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
    public function resolve($value = null): void
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
