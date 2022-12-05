<?php

namespace Illuminate\Console\Process;

use Closure;
use Illuminate\Contracts\Console\Process\ProcessResult as ProcessResultContract;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;

class Factory
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * Indicates if the process factory has faked process handlers.
     *
     * @var bool
     */
    protected $recording = false;

    /**
     * All of the recorded processes.
     *
     * @var array
     */
    protected $recorded = [];

    /**
     * The registered fake handler callbacks.
     *
     * @var array
     */
    protected $fakeHandlers = [];

    /**
     * Indicates that an exception should be thrown if any process is not faked.
     *
     * @var bool
     */
    protected $preventStrayProcesses = false;

    /**
     * Create a new fake process response for testing purposes.
     *
     * @param  string  $output
     * @param  string  $errorOutput
     * @param  int  $exitCode
     * @return \Illuminate\Console\Process\FakeProcessResult
     */
    public function result($output = '', $errorOutput = '', $exitCode = 0)
    {
        return new FakeProcessResult(
            output: $output,
            errorOutput: $errorOutput,
            exitCode: $exitCode,
        );
    }

    /**
     * Begin describing a fake process lifecycle.
     *
     * @return \Illuminate\Console\Process\FakeProcessDescription
     */
    public function describe()
    {
        return new FakeProcessDescription;
    }

    /**
     * Indicate that the process factory should fake processes.
     *
     * @param  \Closure|array|null  $callback
     * @return $this
     */
    public function fake(Closure|array $callback = null)
    {
        $this->recording = true;

        if (is_null($callback)) {
            $this->fakeHandlers = ['*' => fn () => new FakeProcessResult];

            return $this;
        }

        if ($callback instanceof Closure) {
            $this->fakeHandlers = ['*' => $callback];

            return $this;
        }

        foreach ($callback as $command => $handler) {
            $this->fakeHandlers[is_numeric($command) ? '*' : $command] = $handler instanceof Closure
                    ? $handler
                    : fn () => $handler;
        }

        return $this;
    }

    /**
     * Determine if the process factory has fake process handlers and is recording processes.
     *
     * @return bool
     */
    public function isRecording()
    {
        return $this->recording;
    }

    /**
     * Record the given process if processes should be recorded.
     *
     * @param  \Illuminate\Console\Process\PendignProcess  $process
     * @param  \Illuminate\Contracts\Console\Process\ProcessResult  $result
     * @return $this
     */
    public function recordIfRecording(PendingProcess $process, ProcessResultContract $result)
    {
        if ($this->isRecording()) {
            $this->record($process, $result);
        }

        return $this;
    }

    /**
     * Record the given process.
     *
     * @param  \Illuminate\Console\Process\PendignProcess  $process
     * @param  \Illuminate\Contracts\Console\Process\ProcessResult  $result
     * @return $this
     */
    public function record(PendingProcess $process, ProcessResultContract $result)
    {
        $this->recorded[] = [$process, $result];

        return $this;
    }

    /**
     * Indicate that an exception should be thrown if any process is not faked.
     *
     * @param  bool  $prevent
     * @return $this
     */
    public function preventStrayProcesses($prevent = true)
    {
        $this->preventStrayProcesses = $prevent;

        return $this;
    }

    /**
     * Determine if stray processes are being prevented.
     *
     * @return bool
     */
    public function preventingStrayProcesses()
    {
        return $this->preventStrayProcesses;
    }

    /**
     * Assert that a process was recorded matching a given truth test.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function assertRan(Closure $callback)
    {
        PHPUnit::assertTrue(
            collect($this->recorded)->filter(function ($pair) use ($callback) {
                return $callback($pair[0], $pair[1]);
            })->count() > 0,
            'An expected external process was not invoked.'
        );

        return $this;
    }

    /**
     * Assert that a process was not recorded matching a given truth test.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function assertNotRan(Closure $callback)
    {
        PHPUnit::assertTrue(
            collect($this->recorded)->filter(function ($pair) use ($callback) {
                return $callback($pair[0], $pair[1]);
            })->count() === 0,
            'An unexpected external process was invoked.'
        );

        return $this;
    }

    /**
     * Assert that no processes were recorded.
     *
     * @return $this
     */
    public function assertNothingRan()
    {
        PHPUnit::assertEmpty(
            $this->recorded,
            'An unexpected external process was invoked.'
        );

        return $this;
    }

    /**
     * Create a new pending process associated with this factory.
     *
     * @return \Illuminate\Console\Process\PendingProcess
     */
    protected function newPendingProcess()
    {
        return (new PendingProcess($this))->withFakeHandlers($this->fakeHandlers);
    }

    /**
     * Dynamically proxy methods to a new pending process instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->newPendingProcess()->{$method}(...$parameters);
    }
}
