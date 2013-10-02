<?php namespace Illuminate\Cache;

use Illuminate\Redis\Database as Redis;

class RedisStore implements StoreInterface {

	/**
	 * The Redis database connection.
	 *
	 * @var \Illuminate\Redis\Database
	 */
	protected $redis;

	/**
	 * A string that should be prepended to keys.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * The Redis connection that should be used.
	 *
	 * @var string
	 */
	protected $connection;

	/**
	 * Create a new Redis store.
	 *
	 * @param  \Illuminate\Redis\Database  $redis
	 * @param  string  $prefix
	 * @param  string  $connection
	 * @return void
	 */
	public function __construct(Redis $redis, $prefix = '', $connection = 'default')
	{
		$this->redis = $redis;
		$this->prefix = $prefix.':';
		$this->connection = $connection;
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function get($key)
	{
		if ( ! is_null($value = $this->connection()->get($this->prefix.$key)))
		{
			return is_numeric($value) ? $value : unserialize($value);
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
	public function put($key, $value, $minutes)
	{
		$value = is_numeric($value) ? $value : serialize($value);

		$this->connection()->set($this->prefix.$key, $value);

		$this->connection()->expire($this->prefix.$key, $minutes * 60);
	}

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function increment($key, $value = 1)
	{
		return $this->connection()->incrby($this->prefix.$key, $value);
	}

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function decrement($key, $value = 1)
	{
		return $this->connection()->decrby($this->prefix.$key, $value);
	}

	/**
	 * Store an item in the cache indefinitely.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function forever($key, $value)
	{
		$value = is_numeric($value) ? $value : serialize($value);

		$this->connection()->set($this->prefix.$key, $value);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		$this->connection()->del($this->prefix.$key);
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->connection()->flushdb();
	}

	/**
	 * Begin executing a new section operation.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Cache\RedisSection
	 */
	public function section($name)
	{
		return new RedisSection($this, $name);
	}

	/**
	 * Get the Redis connection instance.
	 *
	 * @return \Predis\Connection\SingleConnectionInterface
	 */
	public function connection()
	{
		return $this->redis->connection($this->connection);
	}

	/**
	 * Set the connection name to be used.
	 *
	 * @param  string  $connection
	 * @return void
	 */
	public function setConnection($connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Get the Redis database instance.
	 *
	 * @return \Illuminate\Redis\Database
	 */
	public function getRedis()
	{
		return $this->redis;
	}

	/**
	 * Get the cache key prefix.
	 *
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

}