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
        // SQLite3
        if (preg_match('#(column(s)? .* (is|are) not unique|UNIQUE constraint failed: .*)#i', $exception->getMessage())) {
            return true;
        }

        // MySQL
        if (preg_match('#SQLSTATE\[23000\]: Integrity constraint violation: 1062#i', $exception->getMessage())) {
            return true;
        }

        // PostgreSQL
        if (preg_match('#SQLSTATE\[23505\]#i', $exception->getMessage())) {
            return true;
        }

        // SQLServer
        if (preg_match('#SQLSTATE\[23000\]:.*Cannot insert duplicate key row in object.*#i', $exception->getMessage())) {
            return true;
        }

        return false;
    }
}
