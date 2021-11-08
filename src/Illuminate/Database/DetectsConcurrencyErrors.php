<?php

namespace Illuminate\Database;

use Illuminate\Support\Str;
use PDOException;
use Throwable;

trait DetectsConcurrencyErrors
{
    /**
     * A configurable check for a concurrency error exception.
     *
     * @var callable|null
     */
    protected $concurrencyErrorCheck;

    /**
     * Set a custom check to be used in the concurrency error check.
     *
     * @param  callable  $check
     * @return $this
     */
    public function setConcurrencyErrorCheck(callable $check)
    {
        $this->concurrencyErrorCheck = $check;

        return $this;
    }

    /**
     * Determine if the given exception was caused by a concurrency error such as a deadlock or serialization failure.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function causedByConcurrencyError(Throwable $e)
    {
        if (
            is_callable($this->concurrencyErrorCheck)
            && call_user_func($this->concurrencyErrorCheck, $e)
        ) {
            return true;
        }

        if ($e instanceof PDOException && ($e->getCode() === 40001 || $e->getCode() === '40001')) {
            return true;
        }

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
