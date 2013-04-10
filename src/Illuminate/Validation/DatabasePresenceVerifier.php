<?php namespace Illuminate\Validation;

use Illuminate\Database\ConnectionResolverInterface;

class DatabasePresenceVerifier implements PresenceVerifierInterface {

	/**
	 * The database connection instance.
	 *
	 * @var  \Illuminate\Database\ConnectionResolverInterface
	 */
	protected $db;

	/**
	 * The database connection to use.
	 *
	 * @var string
	 */
	protected $connection = null;

	/**
	 * Create a new database presence verifier.
	 *
	 * @param  \Illuminate\Database\ConnectionResolverInterface  $db
	 * @return void
	 */
	public function __construct(ConnectionResolverInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * Count the number of objects in a collection having the given value.
	 *
	 * @param  string  $collection
	 * @param  string  $column
	 * @param  string  $value
	 * @param  int     $excludeId
	 * @param  string  $idColumn
	 * @return int
	 */
	public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null)
	{
		$query = $this->table($collection)->where($column, '=', $value);

		if ( ! is_null($excludeId))
		{
			$query->where($idColumn ?: 'id', '<>', $excludeId);
		}

		return $query->count();
	}

	/**
	 * Count the number of objects in a collection with the given values.
	 *
	 * @param  string  $collection
	 * @param  string  $column
	 * @param  array   $values
	 * @return int
	 */
	public function getMultiCount($collection, $column, array $values)
	{
		return $this->table($collection)->whereIn($column, $values)->count();
	}

	/**
	 * Get a query builder for the given table.
	 *
	 * @param  string  $table
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function table($table)
	{
		return $this->db->connection($this->connection)->table($table);
	}

	/**
	 * Set the connection to be used.
	 *
	 * @param  string  $connection
	 * @return void
	 */
	public function setConnection($connection)
	{
		$this->connection = $connection;
	}

}