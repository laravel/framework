<?php namespace Illuminate\Database\Connectors;

use PDO;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SqlServerConnection;

class ConnectionFactory {

	/**
	 * Establish a PDO connection based on the configuration.
	 *
	 * @param  array  $config
	 * @return Illuminate\Database\Connection
	 */
	public function make(array $config)
	{
		if ( ! isset($config['prefix']))
		{
			$config['prefix'] = '';
		}

		$pdo = $this->createConnector($config)->connect($config);

		return $this->createConnection($config['driver'], $pdo, $config['database'], $config['prefix']);
	}

	/**
	 * Create a connector instance based on the configuration.
	 *
	 * @param  array  $config
	 * @return Illuminate\Database\Connectors\ConnectorInterface
	 */
	public function createConnector(array $config)
	{
		if ( ! isset($config['driver']))
		{
			throw new \InvalidArgumentException("A driver must be specified.");
		}

		switch ($config['driver'])
		{
			case 'mysql':
				return new MySqlConnector;

			case 'pgsql':
				return new PostgresConnector;

			case 'sqlite':
				return new SQLiteConnector;

			case 'sqlsrv':
				return new SqlServerConnector;
		}

		throw new \InvalidArgumentException("Unsupported driver [{$config['driver']}");
	}

	/**
	 * Create a new connection instance.
	 *
	 * @param  string  $driver
	 * @param  PDO     $connection
	 * @param  string  $database
	 * @param  string  $tablePrefix
	 * @return Illuminate\Database\Connection
	 */
	protected function createConnection($driver, PDO $connection, $database, $tablePrefix = '')
	{
		switch ($driver)
		{
			case 'mysql':
				return new MySqlConnection($connection, $database, $tablePrefix);

			case 'pgsql':
				return new PostgresConnection($connection, $database, $tablePrefix);

			case 'sqlite':
				return new SQLiteConnection($connection, $database, $tablePrefix);

			case 'sqlsrv':
				return new SqlServerConnection($connection, $database, $tablePrefix);
		}

		throw new \InvalidArgumentException("Unsupported driver [$driver]");
	}

}