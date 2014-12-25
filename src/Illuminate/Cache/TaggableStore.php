<?php namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store as StoreContract;

abstract class TaggableStore implements StoreContract {

	/**
	 * Begin executing a new tags operation.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Cache\TaggedCache
	 */
	public function section($name)
	{
		return $this->tags($name);
	}

	/**
	 * Begin executing a new tags operation.
	 *
	 * @param  array|mixed  $names
	 * @return \Illuminate\Cache\TaggedCache
	 */
	public function tags($names)
	{
		return new TaggedCache($this, new TagSet($this, is_array($names) ? $names : func_get_args()));
	}

}
