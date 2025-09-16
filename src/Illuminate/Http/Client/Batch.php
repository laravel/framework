<?php

namespace Illuminate\Http\Client;

use Carbon\CarbonImmutable;
use Closure;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Promise\Utils as PromiseUtils;
use GuzzleHttp\Utils;

/**
 * @mixin \Illuminate\Http\Client\Factory
 */
class Batch
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
     * The list of requests.
     *
     * @var array<array-key, \Illuminate\Http\Client\PendingRequest>
     */
    protected $requests = [];

    /**
     * The total number of requests that belong to the batch.
     *
     * @var int
     */
    public $totalRequests = 0;

    /**
     * The total number of requests that are still pending.
     *
     * @var int
     */
    public $pendingRequests = 0;

    /**
     * The total number of requests that have failed.
     *
     * @var int
     */
    public $failedRequests = 0;

    /**
     * The date indicating when the batch was created.
     *
     * @var \Carbon\CarbonImmutable
     */
    public $createdAt = null;

    /**
     * The date indicating when the batch was cancelled.
     *
     * @var \Carbon\CarbonImmutable|null
     */
    public $cancelledAt = null;

    /**
     * The date indicating when the batch was finished.
     *
     * @var \Carbon\CarbonImmutable|null
     */
    public $finishedAt = null;

    /**
     * The callback to run before the first request from the batch runs.
     *
     * @var \Closure|null
     */
    protected $beforeCallback = null;

    /**
     * The callback to run after a request from the batch succeeds.
     *
     * @var \Closure|null
     */
    protected $progressCallback = null;

    /**
     * The callback to run after a request from the batch fails.
     *
     * @var \Closure|null
     */
    protected $catchCallback = null;

    /**
     * The callback to run if all the requests from the batch succeeded.
     *
     * @var \Closure|null
     */
    protected $thenCallback = null;

    /**
     * The callback to run after all the requests from the batch finish.
     *
     * @var \Closure|null
     */
    protected $finallyCallback = null;

    public function __construct(?Factory $factory = null)
    {
        $this->factory = $factory ?: new Factory();
        $this->handler = Utils::chooseHandler();
        $this->createdAt = new CarbonImmutable();
    }

    /**
     * Add a request to the batch with a key.
     *
     * @param  string  $key
     * @return \Illuminate\Http\Client\PendingRequest
     */
    public function as(string $key)
    {
        $this->incrementPendingRequests();

        return $this->requests[$key] = $this->asyncRequest();
    }

    /**
     * Retrieve the requests in the batch.
     *
     * @return array<array-key, \Illuminate\Http\Client\PendingRequest>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * Add a request to the batch with a numeric index.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\Http\Client\PendingRequest|\GuzzleHttp\Promise\Promise
     */
    public function __call(string $method, array $parameters)
    {
        $this->incrementPendingRequests();

        return $this->requests[] = $this->asyncRequest()->$method(...$parameters);
    }

    /**
     * Get the total number of requests that have been processed by the batch thus far.
     *
     * @return int
     */
    public function processedRequests(): int
    {
        return $this->totalRequests - $this->pendingRequests;
    }

    /**
     * Get the percentage of requests that have been processed (between 0-100).
     *
     * @return int
     */
    public function completion(): int
    {
        return $this->totalRequests > 0 ? round(($this->processedRequests() / $this->totalRequests) * 100) : 0;
    }

    /**
     * Determine if the batch has job failures.
     *
     * @return bool
     */
    public function hasFailures(): bool
    {
        return $this->failedRequests > 0;
    }

    /**
     * Cancel the batch.
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->cancelledAt = new CarbonImmutable();
    }

    /**
     * Determine if the batch was cancelled.
     *
     * @return bool
     */
    public function cancelled(): bool
    {
        return ! is_null($this->cancelledAt);
    }

    /**
     * Determine if the batch has finished executing.
     *
     * @return bool
     */
    public function finished(): bool
    {
        return ! is_null($this->finishedAt);
    }

    /**
     * Register a callback to run before the first request from the batch runs.
     *
     * @param  \Closure  $callback
     * @return Batch
     */
    public function before(Closure $callback): self
    {
        $this->beforeCallback = $callback;

        return $this;
    }

    /**
     * Retrieve the before callback in the batch.
     *
     * @return \Closure|null
     */
    public function beforeCallback(): ?Closure
    {
        return $this->beforeCallback;
    }

    /**
     * Register a callback to run after a request from the batch succeeds.
     *
     * @param  \Closure  $callback
     * @return Batch
     */
    public function progress(Closure $callback): self
    {
        $this->progressCallback = $callback;

        return $this;
    }

    /**
     * Retrieve the progress callback in the batch.
     *
     * @return \Closure|null
     */
    public function progressCallback(): ?Closure
    {
        return $this->progressCallback;
    }

    /**
     * Register a callback to run after a request from the batch fails.
     *
     * @param  \Closure  $callback
     * @return Batch
     */
    public function catch(Closure $callback): self
    {
        $this->catchCallback = $callback;

        return $this;
    }

    /**
     * Retrieve the catch callback in the batch.
     *
     * @return \Closure|null
     */
    public function catchCallback(): ?Closure
    {
        return $this->catchCallback;
    }

    /**
     * Register a callback to run after all the requests from the batch succeed.
     *
     * @param  \Closure  $callback
     * @return Batch
     */
    public function then(Closure $callback): self
    {
        $this->thenCallback = $callback;

        return $this;
    }

    /**
     * Retrieve the then callback in the batch.
     *
     * @return \Closure|null
     */
    public function thenCallback(): ?Closure
    {
        return $this->thenCallback;
    }

    /**
     * Register a callback to run after all the requests from the batch finish.
     *
     * @param  \Closure  $callback
     * @return Batch
     */
    public function finally(Closure $callback): self
    {
        $this->finallyCallback = $callback;

        return $this;
    }

    /**
     * Retrieve the finally callback in the batch.
     *
     * @return \Closure|null
     */
    public function finallyCallback(): ?Closure
    {
        return $this->finallyCallback;
    }

    /**
     * @return array<int|string, \Illuminate\Http\Response|\Illuminate\Http\Client\RequestException>
     */
    public function send(): array
    {
        $results = [];

        $requests = $this->getRequests();
        $beforeCallback = $this->beforeCallback();
        $progressCallback = $this->progressCallback();
        $catchCallback = $this->catchCallback();
        $thenCallback = $this->thenCallback();
        $finallyCallback = $this->finallyCallback();

        if ($beforeCallback !== null) {
            $beforeCallback($this);
        }

        $promises = [];
        foreach ($requests as $key => $item) {
            if ($this->cancelled()) {
                break;
            }

            $promise = match (true) {
                $item instanceof PendingRequest => $item->getPromise(),
                default => $item,
            };

            $promises[$key] = $promise;
        }

        if (! empty($promises)) {
            (new EachPromise($promises, [
                'fulfilled' => function ($result, $key) use (&$results, $progressCallback, $catchCallback) {
                    $results[$key] = $result;
                    $this->decrementPendingRequests();

                    if ($result instanceof Response && $result->successful()) {
                        if ($progressCallback !== null) {
                            $progressCallback($this, $key, $result);
                        }

                        return $result;
                    }

                    if (($result instanceof Response && $result->failed()) || $result instanceof RequestException) {
                        $this->incrementFailedRequests();

                        if ($catchCallback !== null) {
                            $catchCallback($this, $key, $result);
                        }
                    }

                    return $result;
                },
                'rejected' => function ($reason, $key) use (&$results, $catchCallback) {
                    $this->decrementPendingRequests();

                    if ($reason instanceof RequestException) {
                        $this->incrementFailedRequests();

                        if ($catchCallback !== null) {
                            $catchCallback($this, $key, $reason);
                        }
                    }

                    return $reason;
                },
            ]))->promise()->wait();
        }

        if (! $this->cancelled()) {
            if (! $this->hasFailures() && $thenCallback !== null) {
                $thenCallback($this, $results);
            }

            if ($finallyCallback !== null) {
                $finallyCallback($this, $results);
            }

            $this->finishedAt = new CarbonImmutable();
        }

        return $results;
    }

    /**
     * Retrieve a new async pending request.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function asyncRequest()
    {
        return $this->factory->setHandler($this->handler)->async();
    }

    /**
     * Increment the count of total and pending requests in the batch.
     *
     * @return void
     */
    protected function incrementPendingRequests(): void
    {
        $this->totalRequests++;
        $this->pendingRequests++;
    }

    /**
     * Decrement the count of pending requests in the batch.
     *
     * @return void
     */
    protected function decrementPendingRequests(): void
    {
        $this->pendingRequests--;
    }

    /**
     * Increment the count of failed requests in the batch.
     *
     * @return void
     */
    protected function incrementFailedRequests(): void
    {
        $this->failedRequests++;
    }
}
