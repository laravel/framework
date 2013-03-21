<?php namespace Illuminate\Cache; use Closure, ArrayAccess;

abstract class Store implements ArrayAccess {

	/**
	 * The items retrieved from the cache.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * The default number of minutes to store items.
	 *
	 * @var int
	 */
	protected $default = 60;

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	abstract protected function retrieveItem($key);

	/**
	 * Store an item in the cache for a given number of minutes.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $minutes
	 * @return void
	 */
	abstract protected function storeItem($key, $value, $minutes);

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	abstract protected function incrementValue($key, $value);

	/**
	 * Decrement the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	abstract protected function decrementValue($key, $value);

	/**
	 * Store an item in the cache indefinitely.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	abstract protected function storeItemForever($key, $value);

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	abstract protected function removeItem($key);

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	abstract protected function flushItems();

	/**
	 * Determine if an item exists in the cache.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key)
	{
		return ! is_null($this->get($key));
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		$value = $this->retrieveItem($key);

		// If the items are not present in the caches, we will return this default
		// value that was supplied. If it is a Closure we'll execute it so the
		// the execution of an intensive operation will get lazily executed.
		if (is_null($value))
		{
			return value($default);
		}

		return $value;
	}

	/**
	 * Store an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $minutes
	 * @return void
	 */
	public function put($key, $value, $minutes)
	{
		return $this->storeItem($key, $value, $minutes);
	}

	/**
	 * Store an item in the cache if the key does not exist.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $minutes
	 * @return void
	 */
	public function add($key, $value, $minutes)
	{
		if (is_null($this->get($key))) $this->put($key, $value, $minutes);
	}

	/**
	 * Increment the value at a given key.
	 *
	 * @param  string  $key
	 * @param  int     $value
	 * @return void
	 */
	public function increment($key, $value = 1)
	{
		return $this->incrementValue($key, $value);
	}

	/**
	 * Decrement the value at a given key.
	 *
	 * @param  string  $key
	 * @param  int     $value
	 * @return void
	 */
	public function decrement($key, $value = 1)
	{
		return $this->decrementValue($key, $value);
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
		$this->items[$key] = $value;

		return $this->storeItemForever($key, $value);
	}

	/**
	 * Get an item from the cache, or store the default value.
	 *
	 * @param  string   $key
	 * @param  int      $minutes
	 * @param  Closure  $callback
	 * @return 
	 */
	public function remember($key, $minutes, Closure $callback)
	{
		// If the item exists in the cache, we will just return it immediately,
		// otherwise we will execute the given Closure and cache the result
		// of that execution for the given number of minutes in storage.
		if ($this->has($key))
		{
			return $this->get($key);
		}

		$this->put($key, $value = $callback(), $minutes);

		return $value;
	}

	/**
	 * Get an item from the cache, or store the default value forever.
	 *
	 * @param  string   $key
	 * @param  Closure  $callback
	 * @return 
	 */
	public function rememberForever($key, Closure $callback)
	{
		// If the item exists in the cache, we will just return it immediately,
		// otherwise we will execute the given Closure and cache the result
		// of that execution for the given number of minutes. It's easy.
		if ($this->has($key))
		{
			return $this->get($key);
		}

		$this->forever($key, $value = $callback());

		return $value;	
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		unset($this->items[$key]);

		return $this->removeItem($key);
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->items = array();

		return $this->flushItems();
	}

	/**
	 * Get the default cache time.
	 *
	 * @return int
	 */
	public function getDefaultCacheTime()
	{
		return $this->default;
	}

	/**
	 * Set the default cache time in minutes.
	 *
	 * @param  int   $minutes
	 * @return void
	 */
	public function setDefaultCacheTime($minutes)
	{
		$this->default = $minutes;
	}

	/**
	 * Determine if an item is in memory.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function existsInMemory($key)
	{
		return array_key_exists($key, $this->items);
	}

	/**
	 * Get all of the values in memory.
	 *
	 * @return array
	 */
	public function getMemory()
	{
		return $this->items;
	}

	/**
	 * Determine if a cached value exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return $this->has($key);
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->get($key);
	}

	/**
	 * Store an item in the cache for the default time.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->put($key, $value, $this->default);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		return $this->forget($key);
	}

}