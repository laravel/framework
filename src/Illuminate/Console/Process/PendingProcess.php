<?php

namespace Illuminate\Console\Process;

use Illuminate\Console\Process;
use Illuminate\Console\Process\Results\Result;
use Illuminate\Support\Traits\Macroable;

class PendingProcess
{
    use Macroable;

    /**
     * The process's command.
     *
     * @var array<array-key, string>|string
     */
    protected $command = [];

    /**
     * Whether the process should be have a delayed run.
     *
     * @var bool
     */
    protected $delayStart = false;

    /**
     * The factory instance.
     *
     * @var \Illuminate\Console\Process\Factory
     */
    protected $factory;

    /**
     * The process's path.
     *
     * @var string|null
     */
    protected $path;

    /**
     * The process's timeout.
     *
     * @var float|null
     */
    protected $timeout = 60.0;

    /**
     * The stub callables that will handle processes.
     *
     * @var array<int, callable(\Illuminate\Console\Process): (\Illuminate\Console\Contracts\ProcessResult|null)>
     */
    protected $stubs = [];

    /**
     * The callbacks that should execute before the process starts.
     *
     * @var array<int, callable(\Illuminate\Console\Process): mixed>
     */
    protected $beforeStartCallbacks = [];

    /**
     * Creates a new Pending Process instance.
     *
     * @param  \Illuminate\Console\Process\Factory  $factory
     * @return void
     */
    public function __construct($factory)
    {
        $this->factory = $factory;
    }

    /**
     * Ensures the process's run is delayed.
     *
     * @return $this
     */
    public function delayStart()
    {
        $this->delayStart = true;

        return $this;
    }

    /**
     * Dump the process and end the script before start the process.
     *
     * @return $this
     */
    public function dd()
    {
        return $this->beforeStart(function ($process) {
            dd($process);
        });
    }

    /**
     * Dump the process before start the process.
     *
     * @return $this
     */
    public function dump()
    {
        return $this->beforeStart(function ($process) {
            dump($process);
        });
    }

    /**
     * Register a stub callable that will intercept processes and be able to return stub process result.
     *
     * @param  array<int, callable(\Illuminate\Console\Process): \Illuminate\Console\Contracts\ProcessResult>  $callbacks
     * @return $this
     */
    public function stubs($callbacks)
    {
        $this->stubs = $callbacks;

        return $this;
    }

    /**
     * Sets the process's arguments.
     *
     * @param  array<array-key, string>|string  $command
     * @return $this
     */
    public function command($command)
    {
        return tap($this, fn () => $this->command = $command);
    }

    /**
     * Sets the process's path.
     *
     * @param  string  $path
     * @return $this
     */
    public function path($path)
    {
        return tap($this, fn () => $this->path = $path);
    }

    /**
     * Sets the process's timeout.
     *
     * @param  float|null  $timeout
     * @return $this
     */
    public function timeout($timeout)
    {
        return tap($this, fn () => $this->timeout = $timeout);
    }

    /**
     * Disables process's timeout.
     *
     * @return $this
     */
    public function forever()
    {
        return tap($this, fn () => $this->timeout = null);
    }

    /**
     * Starts a new process with the given arguments.
     *
     * @param  array<array-key, string>|string|null  $command
     * @return \Illuminate\Console\Contracts\ProcessResult
     */
    public function run($command = null)
    {
        if (! is_null($command)) {
            $this->command($command);
        }

        $process = is_iterable($this->command)
            ? new Process($this->command)
            : Process::fromShellCommandline((string) $this->command);

        $process->setWorkingDirectory((string) ($this->path ?? getcwd()));
        $process->setTimeout($this->timeout);

        return $this->delayStart
            ? new DelayedStart(fn () => $this->start($process))
            : $this->start($process);
    }

    /**
     * Starts the given process.
     *
     * @param  \Illuminate\Console\Process  $process
     * @return \Illuminate\Console\Contracts\ProcessResult
     */
    protected function start($process)
    {
        collect($this->beforeStartCallbacks)->each(fn ($callback) => $callback($process));

        foreach ($this->stubs as $callback) {
            if ($result = $callback($process)) {
                /** @var \Illuminate\Console\Process\Results\FakeResult $result */
                return $result->setProcess($process);
            }
        }

        return new Result(tap($process)->start());
    }

    /**
     * Add a new "before start" callback to the process.
     *
     * @param  callable(\Illuminate\Console\Process): mixed  $callback
     * @return $this
     */
    public function beforeStart($callback)
    {
        return tap($this, fn () => $this->beforeStartCallbacks[] = $callback);
    }
}
