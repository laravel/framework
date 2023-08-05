<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\QueryException;

trait InteractsWithQueryException
{
    /**
     * We'll match against the message instead of the exception code because
     * the exception code returns "23000" instead of "1062", which is the
     * error code MySQL should return in UNIQUE constraints violations.
     *
     * @var string
     */
    private const UNIQUE_CONSTRAINT_MYSQL_PATTERN = '#Integrity constraint violation: 1062#i';

    /**
     * SQLite 3.8.2 and above will return the newly formatted error message:
     * "UNIQUE constraint failed: *table_name*.*column_name*", however in
     * older versions, it returns "column *column_name* is not unique".
     *
     * @var string
     */
    private const UNIQUE_CONSTRAINT_SQLITE_PATTERN = '#(column(s)? .* (is|are) not unique|UNIQUE constraint failed: .*)#i';

    /**
     * The error code PostgreSQL returns when we run into a UNIQUE constraint violation.
     *
     * @var string
     */
    private const UNIQUE_CONSTRAINT_POSTGRES_CODE = '23505';

    /**
     * The error message regex pattern for when SQL Server runs into a UNIQUE constraint violation.
     *
     * @var string
     */
    private const UNIQUE_CONSTRAINT_SQLSERVER_PATTERN = '#Cannot insert duplicate key row in object#i';

    /**
     * Checks if the QueryException was caused by a UNIQUE constraint violation.
     *
     * @return bool
     */
    protected function matchesUniqueConstraintException(QueryException $exception)
    {
        if (preg_match(self::UNIQUE_CONSTRAINT_SQLITE_PATTERN, $exception->getMessage())) {
            return true;
        }

        if (preg_match(self::UNIQUE_CONSTRAINT_MYSQL_PATTERN, $exception->getMessage())) {
            return true;
        }

        if (preg_match(self::UNIQUE_CONSTRAINT_SQLSERVER_PATTERN, $exception->getMessage())) {
            return true;
        }

        if (self::UNIQUE_CONSTRAINT_POSTGRES_CODE === $exception->getCode()) {
            return true;
        }

        return false;
    }
}
