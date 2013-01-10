<?php namespace Illuminate\Cache;

use Illuminate\Encrypter;
use Illuminate\Database\Connection;

class DatabaseStore extends Store {

	/**
	 * The database connection instance.
	 *
	 * @var Illuminate\Database\Connection
	 */
	protected $connection;

	/**
	 * The encrypter instance.
	 *
	 * @param  Illuminate\Encrypter
	 */
	protected $encrypter;

	/**
	 * The name of the cache table.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * A string that should be prepended to keys.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Create a new database store.
	 *
	 * @param  Illuminate\Database\Connection  $connection
	 * @param  Illuminate\Encrypter  $encrypter
	 * @param  string  $table
	 * @param  string  $prefix
	 * @return void
	 */
	public function __construct(Connection $connection, Encrypter $encrypter, $table, $prefix = '')
	{
		$this->table = $table;
		$this->prefix = $prefix;
		$this->encrypter = $encrypter;
		$this->connection = $connection;
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	protected function retrieveItem($key)
	{
		$prefixed = $this->prefix.$key;

		$cache = $this->table()->where('key', $prefixed)->first();

		// If we have a cache record we will check the expiration time against current
		// time on the system and see if the record has expired. If it has, we will
		// remove the records from the database table so it isn't returned again.
		if ( ! is_null($cache))
		{
			if (time() >= $cache->expiration)
			{
				return $this->removeItem($key);
			}

			return $this->encrypter->decrypt($cache->value);
		}
	}

	/**
	 * Store an item in the cache for a given number of minutes.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $minutes
	 * @return void
	 */
	protected function storeItem($key, $value, $minutes)
	{
		$key = $this->prefix.$key;

		// All of the cached values in the database are encrypted in case this is used
		// as a session data store by the consumer. We'll also calculate the expire
		// time and place that on the table so we will check it on our retrieval.
		$value = $this->encrypter->encrypt($value);

		$expiration = $this->getTime() + ($minutes * 60);

		try
		{
			$this->table()->insert(compact('key', 'value', 'expiration'));
		}
		catch (\Exception $e)
		{
			$this->table()->where('key', $key)->update(compact('value', 'expiration'));
		}
	}

	/**
	 * Get the current system time.
	 *
	 * @return int
	 */
	protected function getTime()
	{
		return time();
	}

	/**
	 * Store an item in the cache indefinitely.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	protected function storeItemForever($key, $value)
	{
		return $this->storeItem($key, $value, 5256000);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	protected function removeItem($key)
	{
		$this->table()->where('key', $this->prefix.$key)->delete();
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	protected function flushItems()
	{
		$this->table()->delete();
	}

	/**
	 * Get a query builder for the cache table.
	 *
	 * @return Illuminate\Database\Query\Builder
	 */
	protected function table()
	{
		return $this->connection->table($this->table);
	}

	/**
	 * Get the underlying database connection.
	 *
	 * @return Illuminate\Database\Connection
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * Get the encrypter instance.
	 *
	 * @return Illuminate\Encrypter
	 */
	public function getEncrypter()
	{
		return $this->encrypter;
	}

}