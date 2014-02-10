<?php namespace Illuminate\Cache;

interface StoreInterface {

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function get($key);

	/**
	 * Store an item in the cache for a given number of minutes.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  float   $minutes
	 * @return void
	 */
	public function put($key, $value, $minutes);

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function increment($key, $value = 1);

	/**
	 * Decrement the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function decrement($key, $value = 1);

	/**
	 * Store an item in the cache indefinitely.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function forever($key, $value);

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key);

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	public function flush();

	/**
	 * Get the cache key prefix.
	 *
	 * @return string
	 */
	public function getPrefix();

    /**
     * Convert expiry in minutes to the underlying store's native representation
     * This is typically a seconds-from-now offset, but does vary between stores.
     *
     * @param float $minutes
     * @return mixed
     */
    public function getExpiry($minutes);


}
