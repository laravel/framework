<?php

namespace Illuminate\Console\Exceptions;

use Symfony\Component\Process\Exception\RuntimeException;

class ProcessFailedException extends RuntimeException
{
    /**
     * The underlying process instance.
     *
     * @var \Symfony\Component\Process\Process
     */
    protected $process;

    /**
     * The process's result.
     *
     * @var \Illuminate\Console\Contracts\ProcessResult
     */
    protected $result;

    /**
     * Creates a new Process Exception instance.
     *
     * @param  \Symfony\Component\Process\Process  $process
     * @param  \Illuminate\Console\Contracts\ProcessResult  $result
     * @param  \Symfony\Component\Process\Exception\RuntimeException  $original
     * @return void
     */
    public function __construct($process, $result, $original = null)
    {
        parent::__construct(sprintf('The command [%s] failed.', $process->getCommandLine()), $process->getExitCode() ?? 1, $original);

        $this->process = $process;
        $this->result = $result;
    }

    /**
     * Get the underlying process instance.
     *
     * @return \Symfony\Component\Process\Process
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * Get the process's result.
     *
     * @return \Illuminate\Console\Contracts\ProcessResult
     */
    public function getResult()
    {
        return $this->result;
    }
}
