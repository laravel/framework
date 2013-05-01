<?php namespace Illuminate\Database;

use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Connectors\ConnectionFactory;

class Capsule {

	/**
	 * Create a new database capsule instance.
	 *
	 * @param  array  $config
	 * @return void
	 */
	public function __construct(array $config)
	{
		$config = $this->parseConfig($config);

		$this->manager = new DatabaseManager($config, $this->getFactory());
	}

	/**
	 * Prepare the Eloquent ORM for use.
	 *
	 * @return void
	 */
	public function bootEloquent()
	{
		Eloquent\Model::setConnectionResolver($this->manager);
	}

	/**
	 * Get a database connection instance.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Database\Connection
	 */
	public function connection($name = null)
	{
		return $this->manager->connection($name);
	}

	/**
	 * Get a database schema builder instance.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Database\Schema\Builder
	 */
	public function schema($name = null)
	{
		return $this->connection($name)->getSchemaBuilder();
	}

	/**
	 * Prepare the passed configuration for the manager.
	 *
	 * @param  array  $config
	 * @return array
	 */
	protected function parseConfig(array $config)
	{
		$parsed = $this->getEmptyConfig();

		foreach ($config as $key => $value)
		{
			$parsed['config']['database.'.$key] = $value;
		}

		return $parsed;
	}

	/**
	 * Get an empty configuration ready for loading.
	 *
	 * @return array
	 */
	protected function getEmptyConfig()
	{
		return array('events' => new Dispatcher, 'config' => array());
	}

	/**
	 * Get a new connection factory instance.
	 *
	 * @return \Illuminate\Database\Connectors\ConnectionFactory
	 */
	protected function getFactory()
	{
		return new ConnectionFactory(new Container);
	}

}