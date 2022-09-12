<?php

namespace Illuminate\Console\Process\Results;

use Illuminate\Console\Contracts\ProcessResult;
use Illuminate\Console\Exceptions\ProcessNotStartedException;
use Illuminate\Console\Process;

class FakeResult implements ProcessResult
{
    use Concerns\Arrayable, Concerns\Exitable, Concerns\Stringable, Concerns\Throwable;

    /**
     * The "after wait" callbacks.
     *
     * @var array<int, callable(\Illuminate\Console\Process, \Illuminate\Console\Contracts\ProcessResult): mixed>
     */
    protected $afterWaitCallbacks = [];

    /**
     * The "on output" callback.
     *
     * @var (callable(int, string): mixed)|null
     */
    protected $onOutput;

    /**
     * The underlying process instance.
     *
     * @var \Illuminate\Console\Process|null
     */
    protected $process;

    /**
     * If the process is running.
     *
     * @var bool
     */
    protected $running = true;

    /**
     * The process's output.
     *
     * @var string
     */
    protected $output;

    /**
     * The process's exit code.
     *
     * @var int
     */
    protected $exitCode;

    /**
     * The process's error output.
     *
     * @var string
     */
    protected $errorOutput;

    /**
     * The process's pid (process identifier), when running.
     *
     * @var int
     */
    protected $pid;

    /**
     * Creates a new Process Result instance.
     *
     * @param  string  $output
     * @param  int  $exitCode
     * @param  string  $errorOutput
     * @param  int  $pid
     * @return void
     */
    public function __construct($output, $exitCode, $errorOutput, $pid)
    {
        $this->output = $output;
        $this->exitCode = $exitCode;
        $this->errorOutput = $errorOutput;
        $this->pid = $pid;
    }

    /**
     * Simulates a "start" of the fake result underlying process.
     *
     * @param  \Illuminate\Console\Process  $process
     * @param  (callable(int, string): mixed)|null  $onOutput
     * @param  array<int, callable(\Illuminate\Console\Process, \Illuminate\Console\Contracts\ProcessResult): mixed>  $afterWaitCallbacks
     * @return $this
     *
     * @internal
     */
    public function start($process, $onOutput, $afterWaitCallbacks)
    {
        $this->afterWaitCallbacks = $afterWaitCallbacks;
        $this->process = $process;
        $this->onOutput = $onOutput;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function output()
    {
        $this->wait();

        return $this->output;
    }

    /**
     * {@inheritDoc}
     */
    public function wait()
    {
        if ($this->running) {
            $this->ensureProcessStarted();

            if ($this->onOutput && $this->output !== '') {
                ($this->onOutput)(Process::STDOUT, $this->output);
            }

            if ($this->onOutput && $this->errorOutput !== '') {
                ($this->onOutput)(Process::STDERR, $this->errorOutput);
            }

            $this->running = false;

            foreach ($this->afterWaitCallbacks as $callback) {
                $callback($this->process, $this);
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function exitCode()
    {
        $this->wait();

        return $this->exitCode;
    }

    /**
     * {@inheritDoc}
     */
    public function errorOutput()
    {
        $this->wait();

        return $this->errorOutput;
    }

    /**
     * {@inheritDoc}
     */
    public function process()
    {
        $this->ensureProcessStarted();

        return $this->process;
    }

    /**
     * Ensures the existing process has started.
     *
     * @return void
     *
     * @throws \Illuminate\Console\Exceptions\ProcessNotStartedException
     */
    protected function ensureProcessStarted()
    {
        if (! $this->process) {
            throw new ProcessNotStartedException;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function pid()
    {
        return $this->running()
            ? $this->pid
            : null;
    }

    /**
     * {@inheritDoc}
     */
    public function running()
    {
        return $this->running;
    }
}
