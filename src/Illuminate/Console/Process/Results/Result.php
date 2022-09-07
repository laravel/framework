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
     * The underlying process instance.
     *
     * @var \Illuminate\Console\Process
     */
    protected $process;

    /**
     * Creates a new Process Result instance.
     *
     * @param  \Illuminate\Console\Process  $process
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
        $this->wait();

        return $this->process->getOutput();
    }

    /**
     * {@inheritDoc}
     */
    public function wait()
    {
        if ($this->process->isRunning()) {
            try {
                $this->process->wait();
            } catch (SymfonyProcessTimedOutException $e) {
                throw new ProcessTimedOutException($this->process, $this, $e);
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
    protected function ensureProcessIsRunning($process)
    {
        if (! $process->isStarted()) {
            throw new ProcessNotStartedException($process);
        }
    }
}
