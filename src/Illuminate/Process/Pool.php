<?php

namespace Illuminate\Process;

use Illuminate\Support\Collection;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use LogicException;

/**
 * @mixin \Illuminate\Process\Factory
 * @mixin \Illuminate\Process\PendingProcess
 */
class Pool
{
    /**
     * The process factory instance.
     *
     * @var \Illuminate\Process\Factory
     */
    protected $factory;

    /**
     * The callback that resolves the pending processes.
     *
     * @var callable
     */
    protected $callback;

    /**
     * The array of pending processes.
     *
     * @var array
     */
    protected $pendingProcesses = [];

    /**
     * The maximum number of processes that may run at the same time.
     *
     * @var int
     */
    protected $concurrency = 0;

    /**
     * Create a new process pool.
     *
     * @param  \Illuminate\Process\Factory  $factory
     * @param  callable  $callback
     */
    public function __construct(Factory $factory, callable $callback)
    {
        $this->factory = $factory;
        $this->callback = $callback;
    }

    /**
     * Add a process to the pool with a key.
     *
     * @param  string  $key
     * @return \Illuminate\Process\PendingProcess
     */
    public function as(string $key)
    {
        return tap($this->factory->newPendingProcess(), function ($pendingProcess) use ($key) {
            $this->pendingProcesses[$key] = $pendingProcess;
        });
    }

    /**
     * Specify the maximum number of processes that may run at the same time.
     *
     * @param  int  $concurrency
     * @return $this
     */
    public function concurrency(int $concurrency)
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    /**
     * Start all of the processes in the pool.
     *
     * @param  callable|null  $output
     * @return \Illuminate\Process\InvokedProcessPool
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function start(?callable $output = null)
    {
        if ($this->concurrency > 0) {
            throw new LogicException('Process pools with a concurrency limit must be run instead of started.');
        }

        return new InvokedProcessPool(
            $this->resolvePendingProcesses()
                ->mapWithKeys(function ($pendingProcess, $key) use ($output) {
                    return [$key => $pendingProcess->start(output: $output ? function ($type, $buffer) use ($key, $output) {
                        $output($type, $buffer, $key);
                    } : null)];
                })
                ->all()
        );
    }

    /**
     * Start and wait for the processes to finish.
     *
     * @return \Illuminate\Process\ProcessPoolResults
     */
    public function run()
    {
        return $this->wait();
    }

    /**
     * Start and wait for the processes to finish.
     *
     * @return \Illuminate\Process\ProcessPoolResults
     */
    public function wait()
    {
        if ($this->concurrency <= 0) {
            return $this->start()->wait();
        }

        return new ProcessPoolResults($this->waitForPendingProcesses());
    }

    /**
     * Run the pending processes, never running more than the pool's concurrency at once.
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function waitForPendingProcesses()
    {
        $pending = $this->resolvePendingProcesses()->all();

        $results = array_fill_keys(array_keys($pending), null);

        $running = [];

        while (! empty($pending) || ! empty($running)) {
            while (! empty($pending) && count($running) < $this->concurrency) {
                $key = array_key_first($pending);

                $running[$key] = $pending[$key]->start();

                unset($pending[$key]);
            }

            foreach ($running as $key => $process) {
                if ($process->running()) {
                    $process->ensureNotTimedOut();

                    continue;
                }

                $results[$key] = $process->wait();

                unset($running[$key]);
            }

            if (! empty($running)) {
                Sleep::usleep(1000);
            }
        }

        return $results;
    }

    /**
     * Resolve the pending processes that have been added to the pool.
     *
     * @return \Illuminate\Support\Collection<array-key, \Illuminate\Process\PendingProcess>
     *
     * @throws \InvalidArgumentException
     */
    protected function resolvePendingProcesses()
    {
        call_user_func($this->callback, $this);

        return (new Collection($this->pendingProcesses))->each(function ($pendingProcess) {
            if (! $pendingProcess instanceof PendingProcess) {
                throw new InvalidArgumentException('Process pool must only contain pending processes.');
            }
        });
    }

    /**
     * Dynamically proxy methods calls to a new pending process.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\Process\PendingProcess
     */
    public function __call($method, $parameters)
    {
        return tap($this->factory->{$method}(...$parameters), function ($pendingProcess) {
            $this->pendingProcesses[] = $pendingProcess;
        });
    }
}
