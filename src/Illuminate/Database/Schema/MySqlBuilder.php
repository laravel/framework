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

		return count($this->connection->select($sql, array($database, $table))) > 0;
	}

}