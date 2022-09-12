<?php

namespace Illuminate\Console\Exceptions;

use Symfony\Component\Process\Exception\RuntimeException;

class ProcessFailedException extends RuntimeException
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
     * @param  \Symfony\Component\Process\Exception\RuntimeException  $original
     * @return void
     */
    public function __construct($result, $original = null)
    {
        $this->result = $result;

        parent::__construct(
            sprintf('The process "%s" failed.', $result->process()->command()),
            $result->process()->getExitCode() ?? 1,
            $original
        );
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
