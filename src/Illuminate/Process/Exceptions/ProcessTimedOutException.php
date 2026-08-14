<?php

namespace Illuminate\Process\Exceptions;

use Illuminate\Contracts\Process\ProcessResult;
use Symfony\Component\Process\Exception\ProcessTimedOutException as SymfonyTimeoutException;
use Symfony\Component\Process\Exception\RuntimeException;

class ProcessTimedOutException extends RuntimeException
{
    /**
     * The process result instance.
     *
     * @var \Illuminate\Contracts\Process\ProcessResult
     */
    public $result;

    /**
     * The original Symfony exception instance.
     *
     * @var \Symfony\Component\Process\Exception\ProcessTimedOutException
     */
    protected $original;

    /**
     * Create a new exception instance.
     *
     * @param  \Symfony\Component\Process\Exception\ProcessTimedOutException  $original
     * @param  \Illuminate\Contracts\Process\ProcessResult  $result
     */
    public function __construct(SymfonyTimeoutException $original, ProcessResult $result)
    {
        $this->result = $result;
        $this->original = $original;

        parent::__construct($original->getMessage(), $original->getCode(), $original);
    }

    /**
     * Determine if the process exceeded its total allotted time.
     *
     * @return bool
     */
    public function isGeneralTimeout()
    {
        return $this->original->isGeneralTimeout();
    }

    /**
     * Determine if the process went too long without returning any output.
     *
     * @return bool
     */
    public function isIdleTimeout()
    {
        return $this->original->isIdleTimeout();
    }

    /**
     * Get the number of seconds the process was allowed to run before timing out.
     *
     * @return float|null
     */
    public function exceededTimeout()
    {
        return $this->original->getExceededTimeout();
    }
}
