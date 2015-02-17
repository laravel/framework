<?php namespace Illuminate\Contracts\Cache;

interface ThreadSafeStore extends Store {

	/**
	 * Store an item in the cache if the key does not exist for a given number of minutes.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $minutes
	 * @return bool
	 */
	public function add($key, $value, $minutes);

}