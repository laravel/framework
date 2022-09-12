<?php

namespace Illuminate\Console\Process\Results;

use Illuminate\Console\Contracts\ProcessResult;
use Illuminate\Console\Exceptions\ProcessNotStartedException;
use Illuminate\Console\Exceptions\ProcessTimedOutException;
use Symfony\Component\Process\Exception\ProcessTimedOutException as SymfonyProcessTimedOutException;

class Result implements ProcessResult
{
    use Concerns\Arrayable, Concerns\Exitable, Concerns\Stringable, Concerns\Throwable;

    /**
     * The "after wait" callbacks.
     *
     * @var array<int, callable(\Illuminate\Console\Process, \Illuminate\Console\Contracts\ProcessResult): mixed>
     */
    protected $afterWaitCallbacks = [];

    /**
     * The underlying process instance.
     *
     * @var \Illuminate\Console\Process
     */
    protected $process;

    /**
     * Creates a new Process Result instance.
     *
     * @param  \Illuminate\Console\Process  $process
     * @param  array<int, callable(\Illuminate\Console\Process, \Illuminate\Console\Contracts\ProcessResult): mixed>  $afterWaitCallbacks
     * @return void
     */
    public function __construct($process, $afterWaitCallbacks)
    {
        $this->afterWaitCallbacks = $afterWaitCallbacks;

        $this->ensureProcessStarted($this->process = $process);
    }

    /**
     * {@inheritDoc}
     */
    public function output()
    {
        $this->wait();

        return $this->process->getOutput();
    }

    /**
     * {@inheritDoc}
     */
    public function running()
    {
        return $this->process->isRunning();
    }

    /**
     * {@inheritDoc}
     */
    public function wait()
    {
        if ($this->running()) {
            try {
                $this->process->wait();
            } catch (SymfonyProcessTimedOutException $e) {
                throw new ProcessTimedOutException($this, $e);
            }

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

        return (int) $this->process->getExitCode();
    }

    /**
     * {@inheritDoc}
     */
    public function errorOutput()
    {
        $this->wait();

        return $this->process->getErrorOutput();
    }

    /**
     * {@inheritDoc}
     */
    public function pid()
    {
        return $this->process->getPid();
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
     * @param  \Illuminate\Console\Process  $process
     * @return void
     *
     * @throws \Illuminate\Console\Exceptions\ProcessNotStartedException
     */
    protected function ensureProcessStarted($process)
    {
        if (! $process->isStarted()) {
            throw new ProcessNotStartedException($process);
        }
    }
}
