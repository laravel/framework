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
	 * @return array|bool
	 */
	public function put($key, $value, $seconds)
	{
		return apc_store($key, $value, $seconds);
	}

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array|bool
	 */
	public function increment($key, $value)
	{
		return apc_inc($key, $value);
	}

	/**
	 * Decremenet the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array|bool
	 */
	public function decrement($key, $value)
	{
		return apc_dec($key, $value);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return array|bool
	 */
	public function delete($key)
	{
		return apc_delete($key);
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	public function flush()
	{
		apc_clear_cache('user');
	}

}