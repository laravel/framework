<?php

namespace Illuminate\Console\Process;

use Illuminate\Console\Contracts\ProcessResult as ProcessResultContract;
use Illuminate\Console\Exceptions\ProcessFailedException;
use Illuminate\Console\Exceptions\ProcessNotStartedException;
use Illuminate\Console\Exceptions\ProcessSignaledException;
use Illuminate\Console\Exceptions\ProcessTimedOutException;
use Symfony\Component\Process\Exception\ProcessSignaledException as SymfonyProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException as SymfonyProcessTimedOutException;
use Symfony\Component\Process\Process;

class ProcessResult implements ProcessResultContract
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
        $this->wait();

        return $this->process->getOutput();
    }

    /**
     * {@inheritDoc}
     */
    public function ok()
    {
        $this->wait();

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
            try {
                $this->process->wait();
            } catch (SymfonyProcessTimedOutException $e) {
                throw new ProcessTimedOutException($this->process, $this, $e);
            } catch (SymfonyProcessSignaledException $e) {
                throw new ProcessSignaledException($this->process, $this, $e);
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

        return $this->process->getExitCode();
    }

    /**
     * {@inheritDoc}
     */
    public function process()
    {
        return $this->process;
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
