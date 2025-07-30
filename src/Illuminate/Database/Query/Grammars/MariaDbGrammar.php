<?php

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinLateralClause;
use RuntimeException;

class MariaDbGrammar extends MySqlGrammar
{
    /**
     * Compile a "lateral join" clause.
     *
     * @param  \Illuminate\Database\Query\JoinLateralClause  $join
     * @param  string  $expression
     * @return string
     *
     * @throws \RuntimeException
     */
    public function compileJoinLateral(JoinLateralClause $join, string $expression): string
    {
        throw new RuntimeException('This database engine does not support lateral joins.');
    }

    /**
     * Compile a "JSON value cast" statement into SQL.
     *
     * @param  string  $value
     * @return string
     */
    public function compileJsonValueCast($value)
    {
        return "json_query({$value}, '$')";
    }

    /**
     * Compile a query to get the number of open connections for a database.
     *
     * @return string
     */
    public function compileThreadCount()
    {
        return 'select variable_value as `Value` from information_schema.global_status where variable_name = \'THREADS_CONNECTED\'';
    }

    /**
     * Determine whether to use a legacy group limit clause for MySQL < 8.0.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return bool
     */
    public function useLegacyGroupLimit(Builder $query)
    {
        return false;
    }

    /**
     * Determine if the connection supports savepoints.
     */
    public function supportsSavepoints(): bool
    {
        return true;
    }

    /**
     * Determine if the connection supports releasing savepoints.
     */
    public function supportsSavepointRelease(): bool
    {
        return true;
    }

    /**
     * Compile the SQL statement to define a savepoint.
     */
    public function compileSavepoint(string $name): string
    {
        return 'SAVEPOINT '.$this->wrapValue($name);
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
     */
    public function compileRollbackToSavepoint(string $name): string
    {
        return 'ROLLBACK TO SAVEPOINT '.$this->wrapValue($name);
    }

    /**
     * Compile the SQL statement to execute a savepoint release.
     */
    public function compileReleaseSavepoint(string $name): string
    {
        return 'RELEASE SAVEPOINT '.$this->wrapValue($name);
    }
}
