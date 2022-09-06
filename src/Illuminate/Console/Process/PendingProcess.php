<?php

namespace Illuminate\Console\Process;

use Illuminate\Console\Process\Results\Result;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\Process\Process;

class PendingProcess
{
    use Macroable;

    /**
     * The process's arguments.
     *
     * @var iterable<array-key, string>
     */
    protected $arguments;

    /**
     * The process's path.
     *
     * @var string|null
     */
    protected $path;

    /**
     * The process's timeout.
     *
     * @var int|null
     */
    protected $timeout = 60;

    /**
     * The stub callables that will handle processes.
     *
     * @var \Illuminate\Support\Collection|null
     */
    protected $stubCallbacks;

    /**
     * The callbacks that should execute before the process starts.
     *
     * @var \Illuminate\Support\Collection|null
     */
    protected $beforeStartCallbacks;

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
     * @param  callable  $callback
     * @return $this
     */
    public function stub($callback)
    {
        $this->stubCallbacks = collect($callback);

        return $this;
    }

    /**
     * Sets the process's arguments.
     *
     * @param  iterable<array-key, string>  $arguments
     * @return $this
     */
    public function withArguments($arguments)
    {
        return tap($this, fn () => $this->arguments = array_merge($this->arguments ?? [], collect($arguments)->values()->toArray()));
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
     * @param  int  $timeout
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
     * @param  iterable<array-key, string>|string  $arguments
     * @return \Illuminate\Console\Contracts\ProcessResult
     */
    public function run($arguments = [])
    {
        $arguments = collect(is_string($arguments) ? str($arguments)->explode(' ') : $arguments)->map(function ($argument) {
            return trim($argument);
        })->toArray();

        $this->withArguments($arguments);

        $process = tap(new Process($this->arguments), function (Process $process) {
            $process->setWorkingDirectory($this->path ?? getcwd());
            $process->setTimeout($this->timeout);
        });

        ($this->beforeStartCallbacks ?? collect())
            ->each(fn ($callback) => $callback($process));

        foreach ($this->stubCallbacks ?? [] as $callback) {
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
     * @param  callable(\Symfony\Component\Process\Process): mixed  $callback
     * @return $this
     */
    protected function beforeStart($callback)
    {
        $this->beforeStartCallbacks ??= collect();

        return tap($this, fn () => $this->beforeStartCallbacks->push($callback));
    }
}
