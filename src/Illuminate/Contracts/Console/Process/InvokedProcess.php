<?php

namespace Illuminate\Contracts\Console\Process;

interface InvokedProcess
{
    /**
     * Get the process ID if the process is still running.
     *
     * @return int|null
     */
    public function id();
}
