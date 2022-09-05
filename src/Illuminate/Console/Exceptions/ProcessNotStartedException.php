<?php

namespace Illuminate\Console\Exceptions;

use Symfony\Component\Process\Exception\LogicException;

class ProcessNotStartedException extends LogicException
{
    /**
     * Creates a new Process Not Started Exception instance.
     *
     * @param  \Symfony\Component\Process\Process|null  $process
     * @return void
     */
    public function __construct($process = null)
    {
        parent::__construct($process
            ? sprintf('The process "%s" failed to start.', $process->getCommandLine())
            : 'The process failed to start.',
        );
    }
}
