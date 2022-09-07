<?php

namespace Illuminate\Console;

use Symfony\Component\Process\Process as BaseProcess;

class Process extends BaseProcess
{
    /**
     * Get the process's command.
     *
     * @return string
     */
    public function command()
    {
        return $this->getCommandLine();
    }

    /**
     * Get the process's path.
     *
     * @return string|null
     */
    public function path()
    {
        return $this->getWorkingDirectory();
    }

    /**
     * Get the process's timeout.
     *
     * @return float|null
     */
    public function timeout()
    {
        return $this->getTimeout();
    }
}
