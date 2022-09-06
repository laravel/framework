<?php

namespace Illuminate\Console\Exceptions;

use Symfony\Component\Process\Exception\RuntimeException;

class ProcessTimedOutException extends RuntimeException
{
    /**
     * The underlying process instance.
     *
     * @var \Illuminate\Console\Process
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
     * @param  \Illuminate\Console\Process  $process
     * @param  \Illuminate\Console\Contracts\ProcessResult  $result
     * @param  \Symfony\Component\Process\Exception\ProcessTimedOutException  $original
     * @return void
     */
    public function __construct($process, $result, $original)
    {
        $this->process = $process;
        $this->result = $result;

        parent::__construct($original->getMessage(), $original->getCode(), $original);
    }

    /**
     * Get the underlying process instance.
     *
     * @return \Illuminate\Console\Process
     */
    public function process()
    {
        return $this->process;
    }

    /**
     * Get the process's result.
     *
     * @return \Illuminate\Console\Contracts\ProcessResult
     */
    public function result()
    {
        return $this->result;
    }
}
