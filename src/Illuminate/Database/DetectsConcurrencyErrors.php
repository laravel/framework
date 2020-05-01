<?php

namespace Illuminate\Database;

use Illuminate\Support\Str;
use PDOException;
use Throwable;

trait DetectsConcurrencyErrors
{
    /**
     * Determine if the given exception was caused by a concurrency error such as a deadlock or serialization failure.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function causedByConcurrencyError(Throwable $e)
    {
        if ($this->causedByDeadlock($e)) {
            return true;
        }

        $message = $e->getMessage();

        return Str::contains($message, [
            'The database file is locked',
            'database is locked',
            'database table is locked',
            'A table in the database is locked',
            'Lock wait timeout exceeded; try restarting transaction',
        ]);
    }

	/**
	 * Determine if the given exception was caused by a deadlock.
	 *
	 * @param  \Throwable  $e
	 * @return bool
	 */
	protected function causedByDeadlock(Throwable $e)
	{
		if ($e instanceof PDOException && $e->getCode() === '40001') {
			return true;
		}

		$message = $e->getMessage();

		return Str::contains($message, [
			'Deadlock found when trying to get lock',
			'deadlock detected',
			'has been chosen as the deadlock victim',
			'WSREP detected deadlock/conflict and aborted the transaction. Try restarting the transaction',
		]);
	}
}
