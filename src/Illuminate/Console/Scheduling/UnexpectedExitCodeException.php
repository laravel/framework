<?php

namespace Illuminate\Console\Scheduling;

use RuntimeException;

class UnexpectedExitCodeException extends RuntimeException
{
    public function __construct(public string $command, public int $exitCode)
    {
        parent::__construct("Scheduled command [{$command}] failed with exit code [{$exitCode}].");
    }
}
