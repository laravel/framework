<?php namespace Illuminate\Database\Schema;

class MySqlBuilder extends Builder {

	/**
	 * Determine if the given table exists.
	 *
	 * @param  string  $table
	 * @return bool
	 */
	public function hasTable($table)
	{
		$sql = $this->grammar->compileTableExists();

		$database = $this->connection->getDatabaseName();

		$table = $this->connection->getTablePrefix().$table;

		return count($this->connection->select($sql, array($database, $table))) > 0;
	}

	/**
	 * Get the column listing for a given table.
	 *
	 * @param  string  $table
	 * @return array
	 */
	protected function getColumnListing($table)
	{
		$sql = $this->grammar->compileColumnExists();

		$database = $this->connection->getDatabaseName();

		$table = $this->connection->getTablePrefix().$table;

		$results = $this->connection->select($sql, array($database, $table));

		return $this->connection->getPostProcessor()->processColumnListing($results);
	}

	/**
	 * Get a full string representation of a column's type.
	 *
	 * @param  string  $table
	 * @param  string  $column
	 * @return string
	 */
	public function getColumnType($table, $column)
	{
		$sql = $this->grammar->compileDescribe($table, $column);

		$result = $this->connection->select($sql);

		$column = $this->connection->getPostProcessor()->processColumnType($result);

		return $this->grammar->compileType($column);
	}

}
