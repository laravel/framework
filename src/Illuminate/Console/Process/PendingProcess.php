<?php

namespace Illuminate\Console\Process;

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
     * The stub callables that will handle processes.
     *
     * @var \Illuminate\Support\Collection|null
     */
    protected $stubCallbacks;

    /**
     * Create a new Pending Process instance.
     *
     *
     * @return void
     */
    public function __construct()
    {
        // ..
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
     * Starts a new process with the given arguments.
     *
     * @param  iterable<array-key, string>|string  $arguments
     * @return \Illuminate\Console\Contracts\ProcessResult
     */
    public function run($arguments)
    {
        $arguments = collect(is_string($arguments) ? str($arguments)->explode(' ') : $arguments)->map(function ($argument) {
            return trim($argument);
        });

        $this->withArguments($arguments);

        $process = tap(new Process($this->arguments), function ($process) {
            $process->setWorkingDirectory($this->path ?? getcwd());

            // ..
        });

        foreach ($this->stubCallbacks ?? [] as $callback) {
            if ($result = $callback($process)) {
                /** @var \Illuminate\Console\Process\FakeProcessResult $result */
                return $result->setProcess($process);
            }
        }

        return new SymfonyProcessResult(tap($process)->start());
    }
}
