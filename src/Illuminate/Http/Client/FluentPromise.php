<?php

namespace Illuminate\Http\Client;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Traits\ForwardsCalls;

class FluentPromise implements PromiseInterface
{
    use ForwardsCalls;

    public function __construct(public PromiseInterface $guzzlePromise)
    {

    }

    public function __call($method, $parameters)
    {
        $result = $this->forwardCallTo($this->guzzlePromise, $method, $parameters);

        if (! $result instanceof PromiseInterface) {
            return $result;
        }

        $this->guzzlePromise = $result;

        return $this;
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
        return $this->guzzlePromise->getState();
    }

    #[\Override]
    public function resolve($value): void
    {
        $this->guzzlePromise->resolve($value);
    }

    #[\Override]
    public function reject($reason): void
    {
        $this->guzzlePromise->reject($reason);
    }

    #[\Override]
    public function cancel(): void
    {
        $this->guzzlePromise->cancel();
    }

    #[\Override]
    public function wait(bool $unwrap = true)
    {
        return $this->__call('wait', [$unwrap]);
    }
}
