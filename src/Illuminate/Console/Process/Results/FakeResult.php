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
     * Creates a new Process Result instance.
     *
     * @param  string  $output
     * @param  int  $exitCode
     * @param  string  $errorOutput
     * @return void
     */
    public function __construct($output, $exitCode, $errorOutput)
    {
        $this->output = $output;
        $this->exitCode = $exitCode;
        $this->errorOutput = $errorOutput;
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
        return $this->output;
    }

    /**
     * {@inheritDoc}
     */
    public function wait()
    {
        if ($this->running) {
            $this->ensureProcessExists();

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
        return $this->exitCode;
    }

    /**
     * {@inheritDoc}
     */
    public function errorOutput()
    {
        return $this->errorOutput;
    }

    /**
     * {@inheritDoc}
     */
    public function process()
    {
        $this->ensureProcessExists();

        return $this->process;
    }

    /**
     * Ensures the existing process has started.
     *
     * @return void
     *
     * @throws \Illuminate\Console\Exceptions\ProcessNotStartedException
     */
    protected function ensureProcessExists()
    {
        if (! $this->process) {
            throw new ProcessNotStartedException;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function running()
    {
        return $this->running;
    }
}
