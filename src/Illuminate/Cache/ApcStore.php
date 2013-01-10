<?php namespace Illuminate\Cache;

class ApcStore extends Store {

	/**
	 * The APC wrapper instance.
	 *
	 * @var Illuminate\Cache\ApcWrapper
	 */
	protected $apc;

	/**
	 * A string that should be prepended to keys.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Create a new APC store.
	 *
	 * @param  Illuminate\Cache\ApcWrapper  $apc
	 * @param  string                       $prefix
	 * @return void
	 */
	public function __construct(ApcWrapper $apc, $prefix = '')
	{
		$this->apc = $apc;
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
		$value = $this->apc->get($this->prefix.$key);

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
		$this->apc->put($this->prefix.$key, $value, $minutes * 60);
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
		$this->apc->delete($this->prefix.$key);
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	protected function flushItems()
	{
		$this->apc->flush();
	}

}