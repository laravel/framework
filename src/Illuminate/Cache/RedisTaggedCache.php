<?php namespace Illuminate\Cache;

class RedisTaggedCache extends TaggedCache {

	/**
	 * Store an item in the cache indefinitely.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function forever($key, $value)
	{
		$key = $this->taggedItemKey($key);

		$this->store->connection()->lpush($this->foreverKey(), $key);

		$this->store->forever($this->taggedItemKey($key), $value);
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	public function flush()
	{
		//
	}

}