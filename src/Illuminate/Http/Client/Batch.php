<?php

namespace Illuminate\Http\Client;

use Carbon\CarbonImmutable;
use Closure;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\EachPromise;
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
     * The array of requests.
     *
     * @var array<array-key, \Illuminate\Http\Client\PendingRequest>
     */
    protected $requests = [];

    /**
     * The total number of requests that belong to the batch.
     *
     * @var non-negative-int
     */
    public $totalRequests = 0;

    /**
     * The total number of requests that are still pending.
     *
     * @var non-negative-int
     */
    public $pendingRequests = 0;

    /**
     * The total number of requests that have failed.
     *
     * @var non-negative-int
     */
    public $failedRequests = 0;

    /**
     * The handler function for the Guzzle client.
     *
     * @var callable
     */
    protected $handler;

    /**
     * The callback to run before the first request from the batch runs.
     *
     * @var (\Closure($this): void)|null
     */
    protected $beforeCallback = null;

    /**
     * The callback to run after a request from the batch succeeds.
     *
     * @var (\Closure($this, int|string, \Illuminate\Http\Response): void)|null
     */
    protected $progressCallback = null;

    /**
     * The callback to run after a request from the batch fails.
     *
     * @var (\Closure($this, int|string, \Illuminate\Http\Response|\Illuminate\Http\Client\RequestException): void)|null
     */
    protected $catchCallback = null;

    /**
     * The callback to run if all the requests from the batch succeeded.
     *
     * @var (\Closure($this, array<int|string, \Illuminate\Http\Response>): void)|null
     */
    protected $thenCallback = null;

    /**
     * The callback to run after all the requests from the batch finish.
     *
     * @var (\Closure($this, array<int|string, \Illuminate\Http\Response>): void)|null
     */
    protected $finallyCallback = null;

    /**
     * If the batch already was sent.
     *
     * @var bool
     */
    protected $inProgress = false;

    /**
     * The date when the batch was created.
     *
     * @var \Carbon\CarbonImmutable
     */
    public $createdAt = null;

    /**
     * The date when the batch finished.
     *
     * @var \Carbon\CarbonImmutable|null
     */
    public $finishedAt = null;

    /**
     * Create a new request batch instance.
     *
     * @return void
     */
    public function __construct(?Factory $factory = null)
    {
        $this->factory = $factory ?: new Factory;
        $this->handler = Utils::chooseHandler();
        $this->createdAt = new CarbonImmutable;
    }

    /**
     * Add a request to the batch with a key.
     *
     * @param  string  $key
     * @return \Illuminate\Http\Client\PendingRequest
     *
     * @throws BatchInProgressException
     */
    public function as(string $key)
    {
        if ($this->inProgress) {
            throw new BatchInProgressException();
        }

        $this->incrementPendingRequests();

        return $this->requests[$key] = $this->asyncRequest();
    }

    /**
     * Register a callback to run before the first request from the batch runs.
     *
     * @param  (\Closure($this): void)  $callback
     * @return Batch
     */
    public function before(Closure $callback): self
    {
        $this->beforeCallback = $callback;

        return $this;
    }

    /**
     * Register a callback to run after a request from the batch succeeds.
     *
     * @param  (\Closure($this, int|string, \Illuminate\Http\Response): void)  $callback
     * @return Batch
     */
    public function progress(Closure $callback): self
    {
        $this->progressCallback = $callback;

        return $this;
    }

    /**
     * Register a callback to run after a request from the batch fails.
     *
     * @param  (\Closure($this, int|string, \Illuminate\Http\Response|\Illuminate\Http\Client\RequestException): void)  $callback
     * @return Batch
     */
    public function catch(Closure $callback): self
    {
        $this->catchCallback = $callback;

        return $this;
    }

    /**
     * Register a callback to run after all the requests from the batch succeed.
     *
     * @param  (\Closure($this, array<int|string, \Illuminate\Http\Response>): void)  $callback
     * @return Batch
     */
    public function then(Closure $callback): self
    {
        $this->thenCallback = $callback;

        return $this;
    }

    /**
     * Register a callback to run after all the requests from the batch finish.
     *
     * @param  (\Closure($this, array<int|string, \Illuminate\Http\Response>): void)  $callback
     * @return Batch
     */
    public function finally(Closure $callback): self
    {
        $this->finallyCallback = $callback;

        return $this;
    }

    /**
     * Send all of the requests in the batch.
     *
     * @return array<int|string, \Illuminate\Http\Response|\Illuminate\Http\Client\RequestException>
     */
    public function send(): array
    {
        $this->inProgress = true;

        if ($this->beforeCallback !== null) {
            call_user_func($this->beforeCallback, $this);
        }

        $results = [];
        $promises = [];

        foreach ($this->requests as $key => $item) {
            $promise = match (true) {
                $item instanceof PendingRequest => $item->getPromise(),
                default => $item,
            };

            $promises[$key] = $promise;
        }

        if (! empty($promises)) {
            (new EachPromise($promises, [
                'fulfilled' => function ($result, $key) use (&$results) {
                    $results[$key] = $result;

                    $this->decrementPendingRequests();

                    if ($result instanceof Response && $result->successful()) {
                        if ($this->progressCallback !== null) {
                            call_user_func($this->progressCallback, $this, $key, $result);
                        }

                        return $result;
                    }

                    if (($result instanceof Response && $result->failed()) ||
                        $result instanceof RequestException) {
                        $this->incrementFailedRequests();

                        if ($this->catchCallback !== null) {
                            call_user_func($this->catchCallback, $this, $key, $result);
                        }
                    }

                    return $result;
                },
                'rejected' => function ($reason, $key) {
                    $this->decrementPendingRequests();

                    if ($reason instanceof RequestException) {
                        $this->incrementFailedRequests();

                        if ($this->catchCallback !== null) {
                            call_user_func($this->catchCallback, $this, $key, $reason);
                        }
                    }

                    return $reason;
                },
            ]))->promise()->wait();
        }

        if (! $this->hasFailures() && $this->thenCallback !== null) {
            call_user_func($this->thenCallback, $this, $results);
        }

        if ($this->finallyCallback !== null) {
            call_user_func($this->finallyCallback, $this, $results);
        }

        $this->finishedAt = new CarbonImmutable;
        $this->inProgress = false;

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
     * Get the total number of requests that have been processed by the batch thus far.
     *
     * @return non-negative-int
     */
    public function processedRequests(): int
    {
        return $this->totalRequests - $this->pendingRequests;
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
     * Determine if the batch has job failures.
     *
     * @return bool
     */
    public function hasFailures(): bool
    {
        return $this->failedRequests > 0;
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

    /**
     * Get the requests in the batch.
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
        if ($this->inProgress) {
            throw new BatchInProgressException();
        }

        $this->incrementPendingRequests();

        return $this->requests[] = $this->asyncRequest()->$method(...$parameters);
    }
}
