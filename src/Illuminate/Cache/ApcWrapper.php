<?php namespace Illuminate\Cache;

class ApcWrapper {

	/**
	 * Get an item from the cache.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function get($key)
	{
		return apc_fetch($key);
	}

	/**
	 * Store an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $seconds
	 * @return void
	 */
	public function put($key, $value, $seconds)
	{
		return apc_store($key, $value, $seconds);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function delete($key)
	{
		return apc_delete($key);
	}

	/**
	 * Remove all itesm from the cache.
	 *
	 * @return void
	 */
	public function flush()
	{
		apc_clear_cache();
	}

}