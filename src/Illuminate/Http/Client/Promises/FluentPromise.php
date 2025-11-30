<?php

namespace Illuminate\Http\Client\Promises;

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
     * @var list<array{?callable, ?callable}>
     */
    protected array $pendingThens = [];

    /**
     * @var list<callable>
     */
    protected array $pendingOtherwises = [];

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
        if ($this->isLazy()) {
            $this->pendingThens[] = [$onFulfilled, $onRejected];

            return $this;
        }

        return $this->__call('then', [$onFulfilled, $onRejected]);
    }

    #[\Override]
    public function otherwise(callable $onRejected): PromiseInterface
    {
        if ($this->isLazy()) {
            $this->pendingOtherwises[] = $onRejected;

            return $this;
        }

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

    public function isLazy(): bool
    {
        return is_callable($this->guzzlePromise);
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

    protected function convertLazyPromiseToPromise(): void
    {
        $this->guzzlePromise = call_user_func($this->guzzlePromise);

        if ($this->pendingThens !== []) {
            array_map(fn (array $pendingThen) => $this->guzzlePromise->then(...$pendingThen), $this->pendingThens);
            $this->pendingThens = [];
        }

        if ($this->pendingOtherwises !== []) {
            array_map(fn (callable $pendingOtherwise) => $this->guzzlePromise->otherwise($pendingOtherwise), $this->pendingOtherwises);
            $this->pendingOtherwises = [];
        }
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
        if ($this->isLazy()) {
            $this->convertLazyPromiseToPromise();
        }

        $result = $this->forwardCallTo($this->guzzlePromise, $method, $parameters);

        if (! $result instanceof PromiseInterface) {
            return $result;
        }

        $this->guzzlePromise = $result;

        return $this;
    }
}
