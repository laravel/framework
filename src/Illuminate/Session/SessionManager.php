<?php namespace Illuminate\Session;

use Illuminate\Support\Manager;

class SessionManager extends Manager {

	/**
	 * Create an instance of the cookie session driver.
	 *
	 * @return \Illuminate\Session\CookieStore
	 */
	protected function createCookieDriver()
	{
		$payload = $this->app['config']->get('session.payload', 'illuminate_payload');

		return new CookieStore($this->app['cookie'], $payload);
	}

	/**
	 * Create an instance of the file session driver.
	 *
	 * @return \Illuminate\Session\FileStore
	 */
	protected function createFileDriver()
	{
		$path = $this->app['config']['session.files'];

		return new FileStore($this->app['files'], $path);
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
	 * Create an instance of the "array" session driver.
	 *
	 * @return \Illuminate\Session\ArrayStore
	 */
	protected function createArrayDriver()
	{
		return new ArrayStore($this->app['cache']->driver('array'));
	}

	/**
	 * Create an instance of the database session driver.
	 *
	 * @return \Illuminate\Session\DatabaseStore
	 */
	protected function createDatabaseDriver()
	{
		$connection = $this->getDatabaseConnection();

		$table = $this->app['config']['session.table'];

		return new DatabaseStore($connection, $this->app['encrypter'], $table);
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
	 * Create an instance of a cache driven driver.
	 *
	 * @return \Illuminate\Session\CacheDrivenStore
	 */
	protected function createCacheBased($driver)
	{
		return new CacheDrivenStore($this->app['cache']->driver($driver));
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