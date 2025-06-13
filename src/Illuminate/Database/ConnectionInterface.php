<?php

namespace Illuminate\Database;

use Closure;

interface ConnectionInterface
{
    /**
     * Begin a fluent query against a database table.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $table
     * @param  string|null  $as
     * @return \Illuminate\Database\Query\Builder
     */
    public function table($table, $as = null);

    /**
     * Get a new raw query expression.
     *
     * @param  mixed  $value
     * @return \Illuminate\Contracts\Database\Query\Expression
     */
    public function raw($value);

    /**
     * Run a select statement and return a single result.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return mixed
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true);

    /**
     * Run a select statement and return the first column of the first row.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return mixed
     *
     * @throws \Illuminate\Database\MultipleColumnsSelectedException
     */
    public function scalar($query, $bindings = [], $useReadPdo = true);

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @param  array  $fetchUsing
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true, array $fetchUsing = []);

    /**
     * Run a select statement against the database and returns a generator.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @param  array  $fetchUsing
     * @return \Generator
     */
    public function cursor($query, $bindings = [], $useReadPdo = true, array $fetchUsing = []);

    /**
     * Run an insert statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function insert($query, $bindings = []);

    /**
     * Run an update statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function update($query, $bindings = []);

    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function delete($query, $bindings = []);

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function statement($query, $bindings = []);

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = []);

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param  string  $query
     * @return bool
     */
    public function unprepared($query);

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings);

    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(Closure $callback, $attempts = 1);

    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction();

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit();

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack();

    /**
     * Get the number of active transactions.
     *
     * @return int
     */
    public function transactionLevel();

    /**
     * Create a savepoint within the current transaction. Optionally provide a callback
     * to be executed following creation of the savepoint. If the callback fails, the transaction
     * will be rolled back to the savepoint. The savepoint will be released after the callback
     * has been executed.
     */
    public function savepoint(string $name, ?callable $callback = null): mixed;

    /**
     * Release a savepoint in the database.
     */
    public function releaseSavepoint(string $name, ?int $level = null): void;

    /**
     * Release all savepoints in the database.
     */
    public function purgeSavepoints(?int $level = null): void;

    /**
     * Rollback to a savepoint in the database.
     */
    public function rollbackToSavepoint(string $name): void;

    /**
     * Determine if a savepoint exists in the database.
     */
    public function hasSavepoint(string $name): bool;

    /**
     * Get the names of all savepoints in the database.
     */
    public function getSavepoints(): array;

    /**
     * Get the current savepoint name.
     */
    public function getCurrentSavepoint(): ?string;

    /**
     * Determine if the connection supports releasing savepoints.
     */
    public function supportsSavepoints(): bool;

    /**
     * Determine if the connection releases savepoints.
     */
    public function supportsSavepointRelease(): bool;

    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param  \Closure  $callback
     * @return array
     */
    public function pretend(Closure $callback);

    /**
     * Get the name of the connected database.
     *
     * @return string
     */
    public function getDatabaseName();
}
