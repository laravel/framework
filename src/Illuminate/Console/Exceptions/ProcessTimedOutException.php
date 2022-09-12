<?php

namespace Illuminate\Console\Exceptions;

use Symfony\Component\Process\Exception\RuntimeException;

class ProcessTimedOutException extends RuntimeException
{
    /**
     * The process's result.
     *
     * @var \Illuminate\Console\Contracts\ProcessResult
     */
    protected $result;

    /**
     * Creates a new Process Exception instance.
     *
     * @param  \Illuminate\Console\Contracts\ProcessResult  $result
     * @param  \Symfony\Component\Process\Exception\ProcessTimedOutException  $original
     * @return void
     */
    public function __construct($result, $original)
    {
        $this->result = $result;

        parent::__construct($original->getMessage(), $original->getCode(), $original);
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
