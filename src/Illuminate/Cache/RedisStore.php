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
	 * Create a new APC store.
	 *
	 * @param  \Illuminate\Redis\Database  $redis
	 * @param  string                     $prefix
	 * @return void
	 */
	public function __construct(Redis $redis, $prefix = '')
	{
		$this->redis = $redis;
		$this->prefix = $prefix.':';
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function get($key)
	{
		if ( ! is_null($value = $this->redis->get($this->prefix.$key)))
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

		$this->redis->set($this->prefix.$key, $value);

		$this->redis->expire($this->prefix.$key, $minutes * 60);
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
		return $this->redis->incrby($this->prefix.$key, $value);
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
		return $this->redis->decrby($this->prefix.$key, $value);
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

		$this->redis->set($this->prefix.$key, $value);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		$this->redis->del($this->prefix.$key);
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->redis->flushdb();
	}

	/**
	 * Begin executing a new section operation.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Cache\Section
	 */
	public function section($name)
	{
		return new RedisSection($this, $name);
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