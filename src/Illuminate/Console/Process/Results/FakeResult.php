<?php

namespace Illuminate\Console\Process\Results;

use Illuminate\Console\Contracts\ProcessResult;
use Illuminate\Console\Exceptions\ProcessNotStartedException;

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
     * Creates a new Process Result instance.
     *
     * @param  string  $output
     * @param  int  $exitCode
     * @return void
     */
    public function __construct($output, $exitCode)
    {
        $this->output = $output;
        $this->exitCode = $exitCode;
    }

    /**
     * Sets the process for the fake result.
     *
     * @param  \Illuminate\Console\Process  $process
     * @return $this
     *
     * @internal
     */
    public function setProcess($process)
    {
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
