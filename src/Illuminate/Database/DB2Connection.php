<?php namespace Illuminate\Database;

class DB2Connection extends Connection {

	/**
	 * Get the default query grammar instance.
	 *
	 * @return \Illuminate\Database\Query\Grammars\Grammars\Grammar
	 */
	protected function getDefaultQueryGrammar()
	{
		return $this->withTablePrefix(new Query\Grammars\DB2Grammar);
	}

	/**
	 * Get the default schema grammar instance.
	 *
	 * @return \Illuminate\Database\Schema\Grammars\Grammar
	 */
	protected function getDefaultSchemaGrammar()
	{
		return $this->withTablePrefix(new Schema\Grammars\DB2Grammar);
	}

	/**
	 * Get the Doctrine DBAL Driver.
	 *
	 * TODO: Build an ODBC Doctrine driver
	 * 
	 * @return \Doctrine\DBAL\Driver
	 */
	/*protected function getDoctrineDriver()
	{
		return new \Doctrine\DBAL\Driver\PDOMySql\Driver;
	}*/

}