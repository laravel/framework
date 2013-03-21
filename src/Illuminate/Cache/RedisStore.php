<?php namespace Illuminate\Cache;

use Illuminate\Redis\Database as Redis;

class RedisStore extends Store {

	/**
	 * The Redis database connection.
	 *
	 * @var Illuminate\Redis\Database
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
	 * @param  Illuminate\Redis\Database  $redis
	 * @param  string                     $prefix
	 * @return void
	 */
	public function __construct(Redis $redis, $prefix = '')
	{
		$this->redis = $redis;
		$this->prefix = $prefix;
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	protected function retrieveItem($key)
	{
		if ( ! is_null($value = $this->redis->get($this->prefix.$key)))
		{
			return unserialize($value);
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
		$this->redis->set($this->prefix.$key, serialize($value));

		$this->redis->expire($this->prefix.$key, $minutes * 60);
	}

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	protected function incrementValue($key, $value)
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
	protected function decrementValue($key, $value)
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
	protected function storeItemForever($key, $value)
	{
		$this->redis->set($this->prefix.$key, serialize($value));
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	protected function removeItem($key)
	{
		$this->redis->del($this->prefix.$key);
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	protected function flushItems()
	{
		$this->redis->flushdb();
	}

	/**
	 * Get the Redis database instance.
	 *
	 * @return Illuminate\Redis\Database
	 */
	public function getRedis()
	{
		return $this->redis;
	}

}