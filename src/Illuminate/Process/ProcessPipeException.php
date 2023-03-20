<?php

namespace Illuminate\Process;

use Exception;

class ProcessPipeException extends Exception
{
    public function __construct(PendingProcess $process)
    {
        parent::__construct("The command '{$process->command}' failed");
    }
}
