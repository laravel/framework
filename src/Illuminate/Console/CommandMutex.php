<?php

declare(strict_types=1);

namespace Illuminate\Console;

use DateTimeInterface;

interface CommandMutex
{
    /**
     * Attempt to obtain a command mutex for the given command
     */
    public function create(Command $command): bool;

    public function exists(Command $command): bool;
}
