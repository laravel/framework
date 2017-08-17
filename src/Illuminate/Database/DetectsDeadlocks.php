<?php

namespace Illuminate\Database;

use Exception;
use Illuminate\Support\Str;

trait DetectsDeadlocks
{
    /**
     * Determine if the given exception was caused by a deadlock.
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function causedByDeadlock(Exception $e)
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'Deadlock found when trying to get lock',
            'deadlock detected',
            'The database file is locked',
            'database is locked',
            'database table is locked',
            'A table in the database is locked',
            'has been chosen as the deadlock victim',
            'Lock wait timeout exceeded; try restarting transaction',
        ]);
    }
}
