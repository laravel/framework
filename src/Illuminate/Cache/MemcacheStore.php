<?php namespace Illuminate\Cache;

use Memcache;

class MemcacheStore implements StoreInterface {

	/**
	 * The Memcache instance.
	 *
	 * @var \Memcache
	 */
	protected $memcache;

	/**
	 * A string that should be prepended to keys.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Create a new Memcache store.
	 *
	 * @param  \Memcache  $memcache
	 * @param  string     $prefix
	 * @return void
	 */
	public function __construct(Memcache $memcache, $prefix = '')
	{
		$this->memcache = $memcache;
		$this->prefix = strlen($prefix) > 0 ? $prefix.':' : '';
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function get($key)
	{
		$flags = false;
		$value = $this->memcache->get($this->prefix.$key, $flags);

		// if $flags has been touched, key was found
		// http://php.net/manual/fr/memcache.get.php#112056
		if ($flags !== false)
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
	public function put($key, $value, $minutes)
	{
		$this->memcache->set(
			$this->prefix.$key,
			$value,
			strlen($value) > 512 ? MEMCACHE_COMPRESSED : 0,
			$minutes * 60
		);
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
		return $this->memcache->increment($this->prefix.$key, $value);
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
		return $this->memcache->decrement($this->prefix.$key, $value);
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
		return $this->put($key, $value, 0);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		$this->memcache->delete($this->prefix.$key);
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->memcache->flush();
	}

	/**
	 * Begin executing a new section operation.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Cache\Section
	 */
	public function section($name)
	{
		return new Section($this, $name);
	}

	/**
	 * Get the underlying Memcache connection.
	 *
	 * @return \Memcache
	 */
	public function getMemcache()
	{
		return $this->memcache;
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
