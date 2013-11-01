<?php namespace Illuminate\Cache;

abstract class TaggableStore {

	/**
	 * Begin executing a new tags operation.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Cache\TaggedCache
	 */
	public function section($name)
	{
		return $this->tag($name);
	}

	/**
	 * Begin executing a new tags operation.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Cache\TaggedCache
	 */
	public function tags($names)
	{
		return new TaggedCache($this, $names);
	}

	/**
	 * Begin executing a new tags operation.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Cache\TaggedCache
	 */
	public function tag($name)
	{
		return $this->tags(array($name));
	}
}
