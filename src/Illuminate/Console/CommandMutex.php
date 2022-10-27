<?php

declare(strict_types=1);

namespace Illuminate\Console;

interface CommandMutex
{
    /**
     * Attempt to obtain a command mutex for the given command.
     *
     * @param  Command  $command
     * @return bool
     */
    public function create($command);

    /**
     * Release the mutex for the given command.
     *
     * @param Command $command
     * @return bool
     */
    public function release($command);

    /**
     * Determine if a command mutex exists for the given command.
     *
     * @param  Command  $command
     * @return bool
     */
    public function exists($command);
}
