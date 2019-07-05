<?php

namespace Illuminate\Database;

use Exception;
use Illuminate\Support\Str;

trait DetectsConcurrencyErrors
{
    /**
     * Determine if the given exception was caused by a concurrency error such as a deadlock or serialization failure.
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function causedByConcurrencyError(Exception $e)
    {
        // First check SQLSTATE code. This covers serialization failures and some deadlocks.
        if( $e instanceof \PDOException && $e->getCode() === '40001' )
        {
            return true;
        }

        // Some deadlocks are not reported using SQLSTATE 40001.
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
            'WSREP detected deadlock/conflict and aborted the transaction. Try restarting the transaction',
        ]);
    }
}
