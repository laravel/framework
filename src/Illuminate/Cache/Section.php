<?php namespace Illuminate\Cache;

use Closure;

class Section {

	/**
	 * The cache store implementation.
	 *
	 * @var \Illuminate\Cache\StoreInterface
	 */
	protected $store;

	/**
	 * The section name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Create a new section instance.
	 *
	 * @param  \Illuminate\Cache\StoreInterface  $store
	 * @param  string  $name
	 * @return void
	 */
	public function __construct(StoreInterface $store, $name)
	{
		$this->name = $name;
		$this->store = $store;
	}

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
		$value = $this->store->get($this->sectionItemKey($key));

		return ! is_null($value) ? $value : value($default);
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
		return $this->store->put($this->sectionItemKey($key), $value, $minutes);
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
		$this->store->increment($this->sectionItemKey($key), $value);
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
		$this->store->decrement($this->sectionItemKey($key), $value);
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
		$this->store->forever($this->sectionItemKey($key), $value);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		$this->store->forget($this->sectionItemKey($key));
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->reset();
	}

	/**
	 * Get an item from the cache, or store the default value.
	 *
	 * @param  string   $key
	 * @param  int      $minutes
	 * @param  Closure  $callback
	 * @return mixed
	 */
	public function remember($key, $minutes, Closure $callback)
	{
		// If the item exists in the cache we will just return this immediately
		// otherwise we will execute the given Closure and cache the result
		// of that execution for the given number of minutes in storage.
		if ($this->has($key)) return $this->get($key);

		$this->put($key, $value = $callback(), $minutes);

		return $value;
	}

	/**
	 * Get an item from the cache, or store the default value forever.
	 *
	 * @param  string   $key
	 * @param  Closure  $callback
	 * @return mixed
	 */
	public function sear($key, Closure $callback)
	{
		return $this->rememberForever($key, $callback);
	}

	/**
	 * Get an item from the cache, or store the default value forever.
	 *
	 * @param  string   $key
	 * @param  Closure  $callback
	 * @return mixed
	 */
	public function rememberForever($key, Closure $callback)
	{
		// If the item exists in the cache we will just return this immediately
		// otherwise we will execute the given Closure and cache the result
		// of that execution for the given number of minutes. It's easy.
		if ($this->has($key)) return $this->get($key);

		$this->forever($key, $value = $callback());

		return $value;
	}

	/**
	 * Get a fully qualfied section item key.
	 *
	 * @param  string  $key
	 * @return string
	 */
	public function sectionItemKey($key)
	{
		return $this->name.':'.$this->sectionId().':'.$key;
	}

	/**
	 * Reset the section, returning a new section identifier
	 *
	 * @return string
	 */
	protected function reset()
	{
		$this->store->forever($this->sectionKey(), $id = uniqid());

		return $id;
	}

	/**
	 * Get the unique section identifier.
	 *
	 * @return string
	 */
	protected function sectionId()
	{
		$id = $this->store->get($this->sectionKey());

		if (is_null($id))
		{
			$id = $this->reset();
		}

		return $id;
	}

	/**
	 * Get the section identifier key.
	 *
	 * @return string
	 */
	protected function sectionKey()
	{
		return 'section:'.$this->name.':key';
	}

}
