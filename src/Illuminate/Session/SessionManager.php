<?php namespace Illuminate\Session;

use Illuminate\Support\Manager;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

class SessionManager extends Manager {

	/**
	 * Call a custom driver creator.
	 *
	 * @param  string  $driver
	 * @return mixed
	 */
	protected function callCustomCreator($driver)
	{
		return $this->buildSession(parent::callCustomCreator($driver));
	}

	/**
	 * Create an instance of the "array" session driver.
	 *
	 * @return \Illuminate\Session\Session
	 */
	protected function createArrayDriver()
	{
		return new Store(new MockArraySessionStorage, null, new FlashBag);
	}

	/**
	 * Create an instance of the native session driver.
	 *
	 * @return Illuminate\Session\Session
	 */
	protected function createNativeDriver()
	{
		$path = $this->app['config']['session.files'];

		return $this->buildSession(new NativeFileSessionHandler($path));
	}

	/**
	 * Create an instance of the database session driver.
	 *
	 * @return \Illuminate\Session\Session
	 */
	protected function createDatabaseDriver()
	{
		$pdo = $this->getDatabaseConnection()->getPdo();

		$table = $this->app['config']['session.table'];

		return $this->buildSession(new PdoSessionHandler($pdo, $this->getDatabaseOptions()));
	}

	/**
	 * Get the database connection for the database driver.
	 *
	 * @return \Illuminate\Database\Connection
	 */
	protected function getDatabaseConnection()
	{
		$connection = $this->app['config']['session.connection'];

		return $this->app['db']->connection($connection);
	}

	/**
	 * Get the database session options.
	 *
	 * @return array
	 */
	protected function getDatabaseOptions()
	{
		$table = $this->app['config']['session.table'];

		return array('db_table' => $table, 'db_id_col' => 'id', 'db_data_col' => 'payload', 'db_time_col' => 'last_activity');
	}

	/**
	 * Create an instance of the APC session driver.
	 *
	 * @return \Illuminate\Session\CacheDrivenStore
	 */
	protected function createApcDriver()
	{
		return $this->createCacheBased('apc');
	}

	/**
	 * Create an instance of the Memcached session driver.
	 *
	 * @return \Illuminate\Session\CacheDrivenStore
	 */
	protected function createMemcachedDriver()
	{
		return $this->createCacheBased('memcached');
	}

	/**
	 * Create an instance of the Wincache session driver.
	 *
	 * @return \Illuminate\Session\CacheDrivenStore
	 */
	protected function createWincacheDriver()
	{
		return $this->createCacheBased('wincache');
	}

	/**
	 * Create an instance of the Redis session driver.
	 *
	 * @return \Illuminate\Session\CacheDrivenStore
	 */
	protected function createRedisDriver()
	{
		return $this->createCacheBased('redis');
	}

	/**
	 * Create an instance of a cache driven driver.
	 *
	 * @return \Illuminate\Session\CacheDrivenStore
	 */
	protected function createCacheBased($driver)
	{
		$minutes = $this->app['config']['session.lifetime'];

		$handler = new CacheBasedSessionHandler($this->app['cache']->driver($driver), $minutes);

		return $this->buildSession($handler);
	}

	/**
	 * Build the session instance.
	 *
	 * @param  \SessionHandlerInterface  $handler
	 * @return \Illuminate\Session\Store
	 */
	protected function buildSession($handler)
	{
		$storage = new NativeSessionStorage($this->getOptions(), $handler);

		return new Store($storage, null, new FlashBag);
	}

	/**
	 * Get the session options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		$config = $this->app['config']['session'];

		return array(
			'cookie_domain' => $config['domain'], 'cookie_lifetime' => $config['lifetime'] * 60,
			'cookie_path' => $config['path'], 'gc_divisor' => $config['lottery'][1],
			'gc_probability' => $config['lottery'][0], 'name' => $config['cookie'],
		);
	}

	/**
	 * Get the default session driver name.
	 *
	 * @return string
	 */
	protected function getDefaultDriver()
	{
		return $this->app['config']['session.driver'];
	}

}