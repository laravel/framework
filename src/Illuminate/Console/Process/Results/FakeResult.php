<?php

namespace Illuminate\Console\Process\Results;

use Illuminate\Console\Contracts\ProcessResult;
use Illuminate\Console\Exceptions\ProcessNotStartedException;
use Illuminate\Console\Process;

class FakeResult implements ProcessResult
{
    use Concerns\Arrayable, Concerns\Exitable, Concerns\Stringable, Concerns\Throwable;

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
     * Simulates a start of the fake result underlying process.
     *
     * @param  \Illuminate\Console\Process  $process
     * @param  (callable(string, int): mixed)|null  $onOutput
     * @return $this
     *
     * @internal
     */
    public function start($process, $onOutput)
    {
        if ($onOutput && $this->output !== '') {
            $onOutput(Process::STDOUT, $this->output);
        }

        if ($onOutput && $this->errorOutput !== '') {
            $onOutput(Process::STDERR, $this->errorOutput);
        }

        return tap($this, fn () => $this->process = $process);
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
        $this->ensureProcessExists();

        $this->running = false;

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

    public function running()
    {
        return $this->running;
    }
}
