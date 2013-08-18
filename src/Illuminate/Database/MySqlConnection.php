<?php namespace Illuminate\Database;

class MySqlConnection extends Connection {

	/**
	 * Get the Doctrine DBAL database connection instance.
	 *
	 * @return \Doctrine\DBAL\Connection
	 */
	public function getDoctrineConnection()
	{
		$connection = parent::getDoctrineConnection();

		// Since schema builder explicitely support enum type, 
		// then we need to ensure Doctrine to map the MySQL enum type to a Doctrine type.
		// @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/mysql-enums.html
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

		return $connection;
	}

	/**
	 * Get a schema builder instance for the connection.
	 *
	 * @return \Illuminate\Database\Schema\Builder
	 */
	public function getSchemaBuilder()
	{
		if (is_null($this->schemaGrammar)) { $this->useDefaultSchemaGrammar(); }

		return new Schema\MySqlBuilder($this);
	}

	/**
	 * Get the default query grammar instance.
	 *
	 * @return \Illuminate\Database\Query\Grammars\Grammars\Grammar
	 */
	protected function getDefaultQueryGrammar()
	{
		return $this->withTablePrefix(new Query\Grammars\MySqlGrammar);
	}

	/**
	 * Get the default schema grammar instance.
	 *
	 * @return \Illuminate\Database\Schema\Grammars\Grammar
	 */
	protected function getDefaultSchemaGrammar()
	{
		return $this->withTablePrefix(new Schema\Grammars\MySqlGrammar);
	}

	/**
	 * Get the Doctrine DBAL Driver.
	 *
	 * @return \Doctrine\DBAL\Driver
	 */
	protected function getDoctrineDriver()
	{
		return new \Doctrine\DBAL\Driver\PDOMySql\Driver;
	}

}