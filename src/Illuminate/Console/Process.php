<?php

namespace Illuminate\Console;

use Symfony\Component\Process\Process as BaseProcess;

class Process extends BaseProcess
{
    /**
     * Get the process's command.
     *
     * @var string
     */
    public function command()
    {
        return $this->getCommandLine();
    }

    /**
     * Get the process's path.
     *
     * @var string
     */
    public function path()
    {
        return $this->getWorkingDirectory();
    }

    /**
     * Get the process's timeout.
     *
     * @var float|null
     */
    public function timeout()
    {
        return $this->getTimeout();
    }
}
