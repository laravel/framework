<?php

namespace Illuminate\Contracts\Database;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;

interface Connection
{
    /**
     * Begin a fluent query against a database table.
     *
     * @param  string $table
     * @return \Illuminate\Database\Query\Builder
     */
    public function table($table);

    /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query();

    /**
     * Get a new raw query expression.
     *
     * @param  mixed $value
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value);

    /**
     * Run a select statement and return a single result.
     *
     * @param  string $query
     * @param  array $bindings
     * @return mixed
     */
    public function selectOne($query, $bindings = []);

    /**
     * Run a select statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     * @return array
     */
    public function selectFromWriteConnection($query, $bindings = []);

    /**
     * Run a select statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     * @param  bool $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true);

    /**
     * Get the PDO connection to use for a select query.
     *
     * @param  bool $useReadPdo
     * @return \PDO
     */
    public function getPdoForSelect($useReadPdo = true);

    /**
     * Run an insert statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     * @return bool
     */
    public function insert($query, $bindings = []);

    /**
     * Run an update statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     * @return int
     */
    public function update($query, $bindings = []);

    /**
     * Run a delete statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     * @return int
     */
    public function delete($query, $bindings = []);

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string $query
     * @param  array $bindings
     * @return bool
     */
    public function statement($query, $bindings = []);

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string $query
     * @param  array $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = []);

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param  string $query
     * @return bool
     */
    public function unprepared($query);

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array $bindings
     * @return array
     */
    public function prepareBindings(array $bindings);

    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure $callback
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(Closure $callback);

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
     * Execute the given callback in "dry run" mode.
     *
     * @param  \Closure $callback
     * @return array
     */
    public function pretend(Closure $callback);

    /**
     * Run a SQL statement and log its execution context.
     *
     * @param  string $query
     * @param  array $bindings
     * @param  \Closure $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    public function run($query, $bindings, Closure $callback);

    /**
     * Run a SQL statement.
     *
     * @param  string $query
     * @param  array $bindings
     * @param  \Closure $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    public function runQueryCallback($query, $bindings, Closure $callback);

    /**
     * Disconnect from the underlying PDO connection.
     *
     * @return void
     */
    public function disconnect();

    /**
     * Reconnect to the database.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function reconnect();

    /**
     * Log a query in the connection's query log.
     *
     * @param  string $query
     * @param  array $bindings
     * @param  float|null $time
     * @return void
     */
    public function logQuery($query, $bindings, $time = null);

    /**
     * Register a database query listener with the connection.
     *
     * @param  \Closure $callback
     * @return void
     */
    public function listen(Closure $callback);

    /**
     * Fire an event for this connection.
     *
     * @param  string $event
     * @return void
     */
    public function fireConnectionEvent($event);

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  int $start
     * @return float
     */
    public function getElapsedTime($start);

    /**
     * Get the current PDO connection.
     *
     * @return \PDO
     */
    public function getPdo();

    /**
     * Get the current PDO connection used for reading.
     *
     * @return \PDO
     */
    public function getReadPdo();

    /**
     * Set the PDO connection.
     *
     * @param  \PDO|null $pdo
     * @return \Illuminate\Database\Connection
     */
    public function setPdo($pdo);

    /**
     * Set the PDO connection used for reading.
     *
     * @param  \PDO|null $pdo
     * @return \Illuminate\Database\Connection
     */
    public function setReadPdo($pdo);

    /**
     * Set the reconnect instance on the connection.
     *
     * @param  callable $reconnector
     * @return \Illuminate\Contracts\Database\Connection
     */
    public function setReconnector(callable $reconnector);

    /**
     * Get the database connection name.
     *
     * @return string|null
     */
    public function getName();

    /**
     * Get an option from the configuration options.
     *
     * @param  string $option
     * @return mixed
     */
    public function getConfig($option);

    /**
     * Get the PDO driver name.
     *
     * @return string
     */
    public function getDriverName();

    /**
     * Get the event dispatcher used by the connection.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher();

    /**
     * Set the event dispatcher instance on the connection.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher $events
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events);

    /**
     * Determine if the connection in a "dry run".
     *
     * @return bool
     */
    public function pretending();

    /**
     * Get the default fetch mode for the connection.
     *
     * @return int
     */
    public function getFetchMode();

    /**
     * Set the default fetch mode for the connection.
     *
     * @param  int $fetchMode
     * @return int
     */
    public function setFetchMode($fetchMode);

    /**
     * Get the connection query log.
     *
     * @return array
     */
    public function getQueryLog();

    /**
     * Clear the query log.
     *
     * @return void
     */
    public function flushQueryLog();

    /**
     * Enable the query log on the connection.
     *
     * @return void
     */
    public function enableQueryLog();

    /**
     * Disable the query log on the connection.
     *
     * @return void
     */
    public function disableQueryLog();

    /**
     * Determine whether we're logging queries.
     *
     * @return bool
     */
    public function logging();

    /**
     * Get the name of the connected database.
     *
     * @return string
     */
    public function getDatabaseName();

    /**
     * Set the name of the connected database.
     *
     * @param  string $database
     * @return string
     */
    public function setDatabaseName($database);

    /**
     * Get the table prefix for the connection.
     *
     * @return string
     */
    public function getTablePrefix();

    /**
     * Set the table prefix in use by the connection.
     *
     * @param  string $prefix
     * @return void
     */
    public function setTablePrefix($prefix);
}
