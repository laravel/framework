<?php

namespace Illuminate\Process;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Process\Exceptions\BatchInProgressException;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Defer\DeferredCallback;
use InvalidArgumentException;
use function Illuminate\Support\defer;

/**
 * @mixin \Illuminate\Process\Factory
 */
class Batch
{
    /**
     * The factory instance.
     *
     * @var \Illuminate\Process\Factory
     */
    protected $factory;

    /**
     * The array of processes.
     *
     * @var array<array-key, \Illuminate\Process\PendingProcess>
     */
    protected $processes = [];

    /**
     * The total number of processes that belong to the batch.
     *
     * @var non-negative-int
     */
    public $totalProcesses = 0;

    /**
     * The total number of processes that are still pending.
     *
     * @var non-negative-int
     */
    public $pendingProcesses = 0;

    /**
     * The total number of processes that have failed.
     *
     * @var non-negative-int
     */
    public $failedProcesses = 0;

    /**
     * The callback to run before the first process from the batch runs.
     *
     * @var (\Closure($this): void)|null
     */
    protected $beforeCallback = null;

    /**
     * The callback to run after a process from the batch succeeds.
     *
     * @var (\Closure($this, int|string, \Illuminate\Process\ProcessResult): void)|null
     */
    protected $progressCallback = null;

    /**
     * The callback to run after a process from the batch fails.
     *
     * @var (\Closure($this, int|string, \Illuminate\Process\ProcessResult|\Illuminate\Process\Exceptions\ProcessFailedException|\Illuminate\Process\Exceptions\ProcessTimedOutException): void)|null
     */
    protected $catchCallback = null;

    /**
     * The callback to run if all the processes from the batch succeeded.
     *
     * @var (\Closure($this, array<int|string, \Illuminate\Process\ProcessResult>): void)|null
     */
    protected $thenCallback = null;

    /**
     * The callback to run after all the processes from the batch finish.
     *
     * @var (\Closure($this, array<int|string, \Illuminate\Process\ProcessResult>): void)|null
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
     * Create a new process batch instance.
     *
     * @param  \Illuminate\Process\Factory|null  $factory
     * @param  callable|null  $callback
     * @return void
     */
    public function __construct(?Factory $factory = null, ?callable $callback = null)
    {
        $this->factory = $factory ?: new Factory;
        $this->createdAt = new CarbonImmutable;

        if ($callback) {
            $callback($this);
        }
    }

    /**
     * Add a process to the batch with a key.
     *
     * @param  string  $key
     * @return \Illuminate\Process\PendingProcess
     */
    public function as(string $key)
    {
        if ($this->inProgress) {
            throw new BatchInProgressException();
        }

        $this->incrementPendingProcesses();
        $this->processes[$key] = $this->factory->newPendingProcess();

        return $this->processes[$key];
    }

    /**
     * Register a callback to run before the first process from the batch runs.
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
     * Register a callback to run after a process from the batch succeeds.
     *
     * @param  (\Closure($this, int|string, \Illuminate\Process\ProcessResult): void)  $callback
     * @return Batch
     */
    public function progress(Closure $callback): self
    {
        $this->progressCallback = $callback;

        return $this;
    }

    /**
     * Register a callback to run after a process from the batch fails.
     *
     * @param  (\Closure($this, int|string, \Illuminate\Process\ProcessResult|\Illuminate\Process\Exceptions\ProcessFailedException|\Illuminate\Process\Exceptions\ProcessTimedOutException): void)  $callback
     * @return Batch
     */
    public function catch(Closure $callback): self
    {
        $this->catchCallback = $callback;

        return $this;
    }

    /**
     * Register a callback to run after all the processes from the batch succeed.
     *
     * @param  (\Closure($this, array<int|string, \Illuminate\Process\ProcessResult>): void)  $callback
     * @return Batch
     */
    public function then(Closure $callback): self
    {
        $this->thenCallback = $callback;

        return $this;
    }

    /**
     * Register a callback to run after all the processes from the batch finish.
     *
     * @param  (\Closure($this, array<int|string, \Illuminate\Process\ProcessResult>): void)  $callback
     * @return Batch
     */
    public function finally(Closure $callback): self
    {
        $this->finallyCallback = $callback;

        return $this;
    }

    /**
     * Defer the batch to run in the background after the current task has finished.
     *
     * @return \Illuminate\Support\Defer\DeferredCallback
     */
    public function defer(): DeferredCallback
    {
        return defer(fn () => $this->run());
    }

    /**
     * Rrun all the processes in the batch.
     *
     * @return array<int|string, \Illuminate\Process\ProcessResult|\Illuminate\Process\Exceptions\ProcessFailedException|\Illuminate\Process\Exceptions\ProcessTimedOutException>
     */
    public function run(): array
    {
        $this->inProgress = true;

        if ($this->beforeCallback !== null) {
            call_user_func($this->beforeCallback, $this);
        }

        $results = collect($this->processes)->each(function ($pendingProcess) {
            if (! $pendingProcess instanceof PendingProcess) {
                throw new InvalidArgumentException('Process batch must only contain pending processes.');
            }
        })->mapWithKeys(function (PendingProcess $pendingProcess, int|string $key) {
            try {
                $result = $pendingProcess->run();

                if ($result->successful()) {
                    $this->decrementPendingProcesses();

                    if ($this->progressCallback !== null) {
                        call_user_func($this->progressCallback, $this, $key, $result);
                    }
                } else {
                    $this->decrementPendingProcesses();
                    $this->incrementFailedProcesses();

                    if ($this->catchCallback !== null) {
                        call_user_func($this->catchCallback, $this, $key, $result);
                    }
                }

                return [$key => $result];
            } catch (ProcessTimedOutException|ProcessFailedException $exception) {
                $this->decrementPendingProcesses();
                $this->incrementFailedProcesses();

                if ($this->catchCallback !== null) {
                    call_user_func($this->catchCallback, $this, $key, $exception);
                }

                return [$key => $exception];
            }
        })->all();

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
     * Get the total number of processes that have been processed by the batch thus far.
     *
     * @return non-negative-int
     */
    public function processedProcesses(): int
    {
        return $this->totalProcesses - $this->pendingProcesses;
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
     * Increment the count of total and pending processes in the batch.
     *
     * @return void
     */
    protected function incrementPendingProcesses(): void
    {
        $this->totalProcesses++;
        $this->pendingProcesses++;
    }

    /**
     * Decrement the count of pending processes in the batch.
     *
     * @return void
     */
    protected function decrementPendingProcesses(): void
    {
        $this->pendingProcesses--;
    }

    /**
     * Determine if the batch has job failures.
     *
     * @return bool
     */
    public function hasFailures(): bool
    {
        return $this->failedProcesses > 0;
    }

    /**
     * Increment the count of failed processes in the batch.
     *
     * @return void
     */
    protected function incrementFailedProcesses(): void
    {
        $this->failedProcesses++;
    }

    /**
     * Get the processes in the batch.
     *
     * @return array<array-key, \Illuminate\Process\PendingProcess>
     */
    public function getProcesses(): array
    {
        return $this->processes;
    }

    /**
     * Add a process to the batch with a numeric index.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\Process\PendingProcess
     */
    public function __call(string $method, array $parameters)
    {
        if ($this->inProgress) {
            throw new BatchInProgressException();
        }

        $this->incrementPendingProcesses();

        $pendingProcess = $this->factory->{$method}(...$parameters);
        $this->processes[] = $pendingProcess;

        return $pendingProcess;
    }
}
