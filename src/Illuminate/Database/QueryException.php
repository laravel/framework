<?php namespace Illuminate\Database;

class QueryException extends \PDOException {

	/**
	 * The SQL for the query.
	 *
	 * @var string
	 */
	protected $sql;

	/**
	 * The bindings for the query.
	 *
	 * @var array
	 */
	protected $bindings;

	/**
	 * Create a new query exception instance.
	 *
	 * @param  string  $sql
	 * @param  array  $bindings
	 * @param  \PDOException $previous
	 * @return void
	 */
	public function __construct($sql, array $bindings, $previous)
	{
		$this->sql = $sql;
		$this->bindings = $bindings;
		$this->previous = $previous;
		$this->code = $previous->getCode();
		$this->errorInfo = $previous->errorInfo;
		$this->message = $this->formatMessage($sql, $bindings, $previous);
	}

	/**
	 * Format the SQL error message.
	 *
	 * @param  string  $sql
	 * @param  array  $bindings
	 * @param  \PDOException $previous
	 * @return string
	 */
	protected function formatMessage($sql, $bindings, $previous)
	{
		return $previous->getMessage().' (SQL: '.str_replace_array('\?', $bindings, $sql).')';
	}

	/**
	 * Get the SQL for the query.
	 *
	 * @return string
	 */
	public function getSql()
	{
		return $this->sql;
	}

	/**
	 * Get the bindings for the query.
	 *
	 * @return array
	 */
	public function getBindings()
	{
		return $this->bindings;
	}

}