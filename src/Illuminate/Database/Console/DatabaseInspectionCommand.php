<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

abstract class DatabaseInspectionCommand extends Command
{
    /**
     * Get the connection configuration details for the given connection.
     *
     * @param  string|null  $database
     * @return array
     */
    protected function getConfigFromDatabase($database)
    {
        $database ??= config('database.default');

        return Arr::except(config('database.connections.'.$database), ['password']);
    }
}
