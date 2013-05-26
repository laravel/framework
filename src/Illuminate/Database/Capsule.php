<?php namespace Illuminate\Database;

use Illuminate\Cache\CacheManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Connectors\ConnectionFactory;

class Capsule {

	/**
	 * Create a new database capsule instance.
	 *
	 * @param  array  $config
	 * @param  \Illuminate\Events\Dispatcher|null  $dispatcher
	 * @param  \Illuminate\Cache\CacheManager|null  $cache
	 * @return void
	 */
	public function __construct(array $config, Dispatcher $dispatcher = null, CacheManager $cache = null)
	{
		$this->config = $this->parseConfig($config, $dispatcher, $cache);

		$this->manager = new DatabaseManager($this->config, $this->getFactory());
	}

	/**
	 * Prepare the Eloquent ORM for use.
	 *
	 * @return void
	 */
	public function bootEloquent()
	{
		Eloquent\Model::setConnectionResolver($this->manager);

		Eloquent\Model::setEventDispatcher($this->config['events']);
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
	 * @param  \Illuminate\Events\Dispatcher|null  $dispatcher
	 * @param  \Illuminate\Cache\CacheManager|null  $cache
	 * @return array
	 */
	protected function parseConfig(array $config, $dispatcher, $cache)
	{
		$parsed = $this->getEmptyConfig($dispatcher, $cache);

		// We will build out the "config" array to look like the database manager will
		// expect it to. This allows us to "trick" the manager to work disconnected
		// from the rest of the framework. We will also put the dispatcher on it.
		foreach ($config as $key => $value)
		{
			$parsed['config']['database.'.$key] = $value;
		}

		return $parsed;
	}

	/**
	 * Get an empty configuration ready for loading.
	 *
	 * @param  \Illuminate\Events\Dispatcher|null  $dispatcher
	 * @param  \Illuminate\Cache\CacheManager|null  $cache
	 * @return array
	 */
	protected function getEmptyConfig($dispatcher, $cache)
	{
		$dispatcher = $dispatcher ?: new Dispatcher(new Container);
		$cache = $cache ?: new CacheManager(new Container);

		return array(
			'events' => $dispatcher,
			'config' => array(),
			'cache' => $cache,
		);
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