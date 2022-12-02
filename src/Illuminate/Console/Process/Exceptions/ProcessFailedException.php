<?php

namespace Illuminate\Console\Process\Exceptions;

use Illuminate\Console\Contracts\ProcessResult;
use Symfony\Component\Console\Exception\RuntimeException;

class ProcessFailedException extends RuntimeException
{
    /**
     * The process result instance.
     *
     * @var \Illuminate\Console\Contracts\ProcessResult
     */
    public $result;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Console\Contracts\ProcessResult  $result
     * @return void
     */
    public function __construct(ProcessResult $result)
    {
        $this->result = $result;

        parent::__construct(
            sprintf('The process "%s" failed.', $result->command()),
            $result->exitCode() ?? 1,
        );
    }
}
