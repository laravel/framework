<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\QueryException;

trait InteractsWithQueryException
{
    /**
     * Checks if the QueryException was caused by a UNIQUE constraint violation.
     *
     * @return bool
     */
    protected function matchesUniqueConstraintException(QueryException $exception)
    {
        // SQLite 3.8.2 and above will return the newly formatted error message:
        // "UNIQUE constraint failed: *table_name*.*column_name*", however in
        // older versions, it returns "column *column_name* is not unique".
        if (preg_match('#(column(s)? .* (is|are) not unique|UNIQUE constraint failed: .*)#i', $exception->getMessage())) {
            return true;
        }

        // We'll match against the message instead of the exception code because
        // the exception code returns "23000" instead of "1062", which is the
        // error code MySQL should return in UNIQUE constraints violations.
        if (preg_match('#Integrity constraint violation: 1062#i', $exception->getMessage())) {
            return true;
        }

        // PostgreSQL
        if (preg_match('#SQLSTATE\[23505\]#i', $exception->getMessage())) {
            return true;
        }

        // SQLServer
        if (preg_match('#Cannot insert duplicate key row in object#i', $exception->getMessage())) {
            return true;
        }

        return false;
    }
}
