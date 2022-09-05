<?php

namespace Illuminate\Console\Process;

use Illuminate\Console\Contracts\ProcessResult;
use Illuminate\Console\Exceptions\ProcessFailedException;
use Illuminate\Console\Exceptions\ProcessNotStartedException;
use Symfony\Component\Process\Process;

class SymfonyProcessResult implements ProcessResult
{
    /**
     * The underlying process instance.
     *
     * @var \Symfony\Component\Process\Process
     */
    protected $process;

    /**
     * Creates a new Process Result instance.
     *
     * @param  \Symfony\Component\Process\Process
     * @return void
     */
    public function __construct($process)
    {
        $this->ensureProcessIsRunning($this->process = $process);
    }

    /**
     * {@inheritDoc}
     */
    public function output()
    {
        if ($this->process->isRunning()) {
            $this->process->wait();
        }

        return $this->process->getOutput();
    }

    /**
     * {@inheritDoc}
     */
    public function ok()
    {
        if ($this->process->isRunning()) {
            $this->process->wait();
        }

        return $this->process->getExitCode() == 0;
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
        if ($this->process->isRunning()) {
            $this->process->wait();
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function throw($callback = null)
    {
        $this->wait();

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
    protected function ensureProcessIsRunning($process)
    {
        throw_unless($process->isStarted(), new ProcessNotStartedException($process));
    }
}
