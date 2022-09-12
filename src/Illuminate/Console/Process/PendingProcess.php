<?php

namespace Illuminate\Console\Process;

use Illuminate\Console\Exceptions\ProcessAlreadyStarted;
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
     * Whether the process should be asynchronous.
     *
     * @var bool
     */
    protected $async = false;

    /**
     * The factory instance.
     *
     * @var \Illuminate\Console\Process\Factory
     */
    protected $factory;

    /**
     * The process's output callback.
     *
     * @var (callable(string, int): mixed)|null
     */
    protected $output;

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
     * The callbacks that should execute after the process waits.
     *
     * @var array<int, callable(\Illuminate\Console\Process, \Illuminate\Console\Contracts\ProcessResult): mixed>
     */
    protected $afterWaitCallbacks = [];

    /**
     * If the process already started.
     *
     * @var bool
     */
    protected $started = false;

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
     * Toggle asynchronicity in the process..
     *
     * @param  bool  $async
     * @return $this
     */
    public function async($async = true)
    {
        $this->async = $async;

        return $this;
    }

    /**
     * Sets the process's command.
     *
     * @param  array<array-key, string>|string  $command
     * @return $this
     */
    public function command($command)
    {
        return tap($this, fn () => $this->command = $command);
    }

    /**
     * Dump the process and end the script before start the process.
     *
     * @return $this
     */
    public function dd()
    {
        return $this->beforeStart(fn ($process) => dd($process));
    }

    /**
     * Dump the process before start the process.
     *
     * @return $this
     */
    public function dump()
    {
        return $this->beforeStart(fn ($process) => dump($process));
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
     * Sets the process's output callback.
     *
     * @param  callable(string, int): mixed  $callback
     * @return $this
     */
    public function output($callback)
    {
        return tap($this, fn () => $this->output = fn ($type, $output) => $callback($output, $type));
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
     * Register a stub callable that will intercept processes and be able to return stub process result.
     *
     * @param  array<int, callable(\Illuminate\Console\Process): (\Illuminate\Console\Contracts\ProcessResult|null)>  $callbacks
     * @return $this
     */
    public function stubs($callbacks)
    {
        return tap($this, fn () => $this->stubs = $callbacks);
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
     * Runs the process and returns the process's result.
     *
     * @param  array<array-key, string>|string|null  $command
     * @param  (callable(string, int): mixed)|null  $output
     * @return \Illuminate\Console\Contracts\ProcessResult
     */
    public function run($command = null, $output = null)
    {
        if ($this->started) {
            throw new ProcessAlreadyStarted();
        }

        $this->started = true;

        if (! is_null($command)) {
            $this->command($command);
        }

        if (! is_null($output)) {
            $this->output($output);
        }

        $process = is_iterable($this->command)
            ? new Process($this->command)
            : Process::fromShellCommandline((string) $this->command);

        $process->setWorkingDirectory((string) ($this->path ?? getcwd()));
        $process->setTimeout($this->timeout);

        return tap($this->start($process, $this->output), function ($result) {
            if (! $this->async) {
                $result->wait();
            }
        });
    }

    /**
     * Starts the process internally and returns and "waitable" process's result instance.
     *
     * @param  \Illuminate\Console\Process  $process
     * @param  (callable(string, int): mixed)|null  $output
     * @return \Illuminate\Console\Contracts\ProcessResult
     */
    protected function start($process, $output)
    {
        collect($this->beforeStartCallbacks)->each(fn ($callback) => $callback($process));

        foreach ($this->stubs as $callback) {
            if ($result = $callback($process)) {
                /** @var \Illuminate\Console\Process\Results\FakeResult $result */
                return value($result)->start($process, $output, $this->afterWaitCallbacks);
            }
        }

        return new Result(tap($process)->start($output), $this->afterWaitCallbacks);
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

    /**
     * Add a new "after wait" callback to the process.
     *
     * @param  callable(\Illuminate\Console\Process, \Illuminate\Console\Contracts\ProcessResult): mixed  $callback
     * @return $this
     */
    public function afterWait($callback)
    {
        return tap($this, fn () => $this->afterWaitCallbacks[] = $callback);
    }
}
