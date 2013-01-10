<?php namespace Illuminate\Database;

use Illuminate\Support\Manager;
use Illuminate\Database\Connectors\ConnectionFactory;

class DatabaseManager implements ConnectionResolverInterface {

	/**
	 * The application instance.
	 *
	 * @var Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * The database connection factory instance.
	 *
	 * @var Illuminate\Database\Connectors\ConnectionFactory
	 */
	protected $factory;

	/**
	 * The active connection instances.
	 *
	 * @var array
	 */
	protected $connections = array();

	/**
	 * Create a new database manager instance.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @param  Illuminate\Database\Connectors\ConnectionFactory  $factory
	 * @return void
	 */
	public function __construct($app, ConnectionFactory $factory)
	{
		$this->app = $app;
		$this->factory = $factory;
	}

	/**
	 * Get a database connection instance.
	 *
	 * @param  string  $name
	 * @return Illuminate\Database\Connection
	 */
	public function connection($name = null)
	{
		$name = $name ?: $this->getDefaultConnection();

		// If we haven't created this connection, we'll create it based on the config
		// provided in the application. Once we've created the connections we will
		// set the "fetch mode" for PDO which determines the query return types.
		if ( ! isset($this->connections[$name]))
		{
			$connection = $this->factory->make($this->getConfig($name));

			$this->connections[$name] = $this->prepare($connection);
		}

		return $this->connections[$name];
	}

	/**
	 * Prepare the database connection instance.
	 *
	 * @param  Illuminate\Database\Connection  $connection
	 * @return Illuminate\Database\Connection
	 */
	protected function prepare(Connection $connection)
	{
		$connection->setFetchMode($this->app['config']['database.fetch']);

		$connection->setEventDispatcher($this->app['events']);

		// We will setup a Closure to resolve the paginator instance on the connection
		// since the Paginator isn't sued on every request and needs quite a few of
		// our dependencies. It'll be more efficient to lazily resolve instances.
		$app = $this->app;

		$connection->setPaginator(function() use ($app)
		{
			return $app['paginator'];
		});

		return $connection;
	}

	/**
	 * Get the configuration for a connection.
	 *
	 * @param  string  $name
	 * @return array
	 */
	protected function getConfig($name)
	{
		$name = $name ?: $this->getDefaultConnection();

		// To get the database connection configuration, we will just pull each of the
		// connection configurations and get the configurations for the given name.
		// If the configuration doesn't exist, we'll throw an exception and bail.
		$connections = $this->app['config']['database.connections'];

		if (is_null($config = array_get($connections, $name)))
		{
			throw new \InvalidArgumentException("Database [$name] not configured.");
		}

		return $config;
	}

	/**
	 * Get the default connection name.
	 *
	 * @return string
	 */
	public function getDefaultConnection()
	{
		return $this->app['config']['database.default'];
	}

	/**
	 * Set the default connection name.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setDefaultConnection($name)
	{
		$this->app['config']['database.default'] = $name;
	}

	/**
	 * Dynamically pass methods to the default connection.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array(array($this->connection(), $method), $parameters);
	}

}