<?php namespace Illuminate\Database;

use Closure;

interface ConnectionInterface {

	/**
	 * Begin a fluent query against a database table.
	 *
	 * @param  string  $table
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function table($table);

	/**
	 * Get a new raw query expression.
	 *
	 * @param  mixed  $value
	 * @return \Illuminate\Database\Query\Expression
	 */
	public function raw($value);

	/**
	 * Run a select statement and return a single result.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return mixed
	 */
	public function selectOne($query, $bindings = array());

	/**
	 * Run a select statement against the database.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return array
	 */
	public function select($query, $bindings = array());

	/**
	 * Run an insert statement against the database.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return bool
	 */
	public function insert($query, $bindings = array());

	/**
	 * Run an update statement against the database.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return int
	 */
	public function update($query, $bindings = array());

	/**
	 * Run a delete statement against the database.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return int
	 */
	public function delete($query, $bindings = array());

	/**
	 * Execute an SQL statement and return the boolean result.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return bool
	 */
	public function statement($query, $bindings = array());

	/**
	 * Run an SQL statement and get the number of rows affected.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return int
	 */
	public function affectingStatement($query, $bindings = array());

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
	 * @param  Closure  $callback
	 * @return mixed
	 *
	 * @throws \Exception
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
	 * @param  Closure  $callback
	 * @return array
	 */
	public function pretend(Closure $callback);

}
