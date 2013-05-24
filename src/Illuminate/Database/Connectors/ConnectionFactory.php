<?php namespace Illuminate\Database\Connectors;

use PDO;
use Illuminate\Container\Container;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SqlServerConnection;

class ConnectionFactory {

	/**
	 * The IoC container instance.
	 *
	 * @var \Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * Create a new connection factory instance.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Establish a PDO connection based on the configuration.
	 *
	 * @param  array   $config
	 * @param  string  $name
	 * @return \Illuminate\Database\Connection
	 */
	public function make(array $config, $name = null)
	{
		$config = $this->parseConfig($config, $name);

		$pdo = $this->createConnector($config)->connect($config);

		return $this->createConnection($config['driver'], $pdo, $config['database'], $config['prefix'], $config);
	}

	/**
	 * Parse and prepare the database configuration.
	 *
	 * @param  array   $config
	 * @param  string  $name
	 * @return array
	 */
	protected function parseConfig(array $config, $name)
	{
		return array_add(array_add($config, 'prefix', ''), 'name', $name);
	}

	/**
	 * Create a connector instance based on the configuration.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Database\Connectors\ConnectorInterface
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

		throw new \InvalidArgumentException("Unsupported driver [{$config['driver']}]");
	}

	/**
	 * Create a new connection instance.
	 *
	 * @param  string  $driver
	 * @param  PDO     $connection
	 * @param  string  $database
	 * @param  string  $prefix
	 * @param  array   $config
	 * @return \Illuminate\Database\Connection
	 */
	protected function createConnection($driver, PDO $connection, $database, $prefix = '', $config = null)
	{
		if ($this->container->bound($key = "db.connection.{$driver}"))
		{
			return $this->container->make($key, array($connection, $database, $prefix, $config));
		}

		switch ($driver)
		{
			case 'mysql':
				return new MySqlConnection($connection, $database, $prefix, $config);

			case 'pgsql':
				return new PostgresConnection($connection, $database, $prefix, $config);

			case 'sqlite':
				return new SQLiteConnection($connection, $database, $prefix, $config);

			case 'sqlsrv':
				return new SqlServerConnection($connection, $database, $prefix, $config);
		}

		throw new \InvalidArgumentException("Unsupported driver [$driver]");
	}

}
