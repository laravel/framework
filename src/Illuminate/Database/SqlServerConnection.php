<?php namespace Illuminate\Database;

class SqlServerConnection extends Connection {

	/**
	 * Execute a Closure within a transaction.
	 *
	 * @param  Closure  $callback
	 * @return mixed
	 */
	public function transaction(Closure $callback)
	{
		$this->pdo->exec('BEGIN TRAN');

		// We'll simply execute the given callback within a try / catch block
		// and if we catch any exception we can rollback the transaction
		// so that none of the changes are persisted to the database.
		try
		{
			$result = $callback($this);

			$this->pdo->exec('COMMIT TRAN');
		}

		// If we catch an exception, we will roll back so nothing gets messed
		// up in the database. Then we'll re-throw the exception so it can
		// be handled how the developer sees fit for their applications.
		catch (\Exception $e)
		{
			$this->pdo->exec('ROLLBACK TRAN');

			throw $e;
		}

		return $result;
	}

	/**
	 * Get the default query grammar instance.
	 *
	 * @return \Illuminate\Database\Query\Grammars\Grammars\Grammar
	 */
	protected function getDefaultQueryGrammar()
	{
		return $this->withTablePrefix(new Query\Grammars\SqlServerGrammar);
	}

	/**
	 * Get the default schema grammar instance.
	 *
	 * @return \Illuminate\Database\Schema\Grammars\Grammar
	 */
	protected function getDefaultSchemaGrammar()
	{
		return $this->withTablePrefix(new Schema\Grammars\SqlServerGrammar);
	}

	/**
	 * Get the Doctrine DBAL Driver.
	 *
	 * @return \Doctrine\DBAL\Driver
	 */
	protected function getDoctrineDriver()
	{
		return new \Doctrine\DBAL\Driver\PDOSqlsrv\Driver;
	}

	/**
	 * Get the default post processor instance.
	 *
	 * @return \Illuminate\Database\Query\Processors\Processor
	 */
	protected function getDefaultPostProcessor()
	{
		return new Query\Processors\SqlServerProcessor;
	}

}