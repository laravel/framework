<?php

namespace Illuminate\Console\Process;

use Illuminate\Console\Contracts\ProcessResult;
use Illuminate\Console\Exceptions\ProcessFailedException;
use Illuminate\Console\Exceptions\ProcessNotStartedException;
use Symfony\Component\Process\Process;

class FakeProcessResult implements ProcessResult
{
    /**
     * The underlying process instance.
     *
     * @var \Symfony\Component\Process\Process
     */
    protected $process;

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
     * @param  \Symfony\Component\Process\Process  $process
     * @return $this
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
    public function ok()
    {
        return $this->exitCode == 0;
    }

    /**
     * {@inheritDoc}
     */
    public function failed()
    {
        return ! $this->ok();
    }

    /**
     * {@inheritDoc}
     */
    public function wait()
    {
        $this->ensureProcessIsRunning();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function throw($callback = null)
    {
        if ($this->failed()) {
            throw new ProcessFailedException($this->process, $this);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function throwIf($condition)
    {
        return $condition ? $this->throw() : $this;
    }

    /**
     * {@inheritDoc}
     */
    public function throwUnless($condition)
    {
        return $condition ? $this : $this->throw();
    }

    /**
     * Ensures the existing process has started.
     *
     * @return void
     *
     * @throws \Illuminate\Console\Process\Exceptions\ProcessNotStartedException
     */
    protected function ensureProcessIsRunning()
    {
        throw_unless($this->process, new ProcessNotStartedException());
    }
}
