<?php namespace Illuminate\Cache; use Memcached;

class MemcachedStore extends Store {

	/**
	 * The Memcached instance.
	 *
	 * @var Memcached
	 */
	protected $memcached;

	/**
	 * A string that should be prepended to keys.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Create a new Memcached store.
	 *
	 * @param  Memcached  $memcached
	 * @param  string     $prefix
	 * @return void
	 */
	public function __construct(Memcached $memcached, $prefix = '')
	{
		$this->prefix = $prefix;
		$this->memcached = $memcached;
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	protected function retrieveItem($key)
	{
		$value = $this->memcached->get($this->prefix.$key);

		if ($value !== false)
		{
			return $value;
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
		$this->memcached->set($this->prefix.$key, $value, $minutes * 60);
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
		$this->memcached->increment($this->prefix.$key, $value);
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
		$this->memcached->decrement($this->prefix.$key, $value);
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
		return $this->storeItem($key, $value, 0);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	protected function removeItem($key)
	{
		$this->memcached->delete($this->prefix.$key);
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	protected function flushItems()
	{
		$this->memcached->flush();
	}

	/**
	 * Get the underlying Memcached connection.
	 *
	 * @return Memcached
	 */
	public function getMemcached()
	{
		return $this->memcached;
	}

}