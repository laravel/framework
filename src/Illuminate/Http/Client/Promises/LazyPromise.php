<?php

namespace Illuminate\Http\Client\Promises;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Traits\ForwardsCalls;
use RuntimeException;

class LazyPromise implements PromiseInterface
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
     * @var list<array{method: "then"|"otherwise", params: list<?callable>}>
     */
    protected array $pending = [];

    protected PromiseInterface $guzzlePromise;

    /**
     * Create a new fluent promise instance.
     *
     * @param  \Closure(): \GuzzleHttp\Promise\PromiseInterface  $promiseBuilder
     */
    public function __construct(protected Closure $promiseBuilder)
    {
    }

    /**
     * Build the promise from the lazy promise builder.
     *
     * @return PromiseInterface
     *
     * @throws \RuntimeException If the promise has already been built
     */
    public function buildPromise(): PromiseInterface
    {
        if (isset($this->guzzlePromise)) {
            throw new RuntimeException('Promise already built');
        }

        $this->guzzlePromise = call_user_func($this->promiseBuilder);


        if ($this->pendingThens !== []) {
            array_map(fn (array $pendingThen) => $this->guzzlePromise->then(...$pendingThen), $this->pendingThens);
            $this->pendingThens = [];
        }

        if ($this->pendingOtherwises !== []) {
            array_map(fn (callable $pendingOtherwise) => $this->guzzlePromise->otherwise($pendingOtherwise), $this->pendingOtherwises);
            $this->pendingOtherwises = [];
        }

        return $this->guzzlePromise;
    }

    /**
     * If the promise has been created from the promise builder.
     *
     * @return bool
     */
    public function promiseNeedsBuilt(): bool
    {
        return ! isset($this->guzzlePromise);
    }

    #[\Override]
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        $this->pending[] = [
            'method' => 'then',
            'params' => [$onFulfilled, $onRejected],
        ];

        return $this;
    }

    #[\Override]
    public function otherwise(callable $onRejected): PromiseInterface
    {
        $this->pending[] = [
            'method' => 'otherwise',
            'params' => [$onRejected],
        ];

        return $this;
    }

    #[\Override]
    public function getState(): string
    {
        return PromiseInterface::PENDING;
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
}
