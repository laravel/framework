<?php namespace Illuminate\Cache;

class RedisSection extends TaggedCache {

	/**
	 * The section name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Creates a new RedisSection
	 *
	 * @param  \Illuminate\Cache\StoreInterface  $store
	 * @param  string  $name
	 * @return void
	 */
	public function __construct(StoreInterface $store, $name)
	{
		parent::$construct($store, array($name));
		$this->name = $name;
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
		$this->store->connection()->lpush($this->foreverKey(), $key);

		parent::forever($key, $value);
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->deleteForeverKeys();

		$this->store->connection()->del($this->foreverKey());

		parent::flush();
	}

	/**
	 * Delete all of the keys that have been stored forever.
	 *
	 * @return void
	 */
	protected function deleteForeverKeys()
	{
		$forever = $this->getForeverKeys();

		if (count($forever) > 0)
		{
			call_user_func_array(array($this->store->connection(), 'del'), $forever);
		}
	}

	/**
	 * Get the keys that have been stored forever.
	 *
	 * @return array
	 */
	protected function getForeverKeys()
	{
		$me = $this;

		return array_map(function($x) use ($me)
		{
			return $me->getPrefix().$me->taggedItemKey($x);

		}, array_unique($this->store->connection()->lrange($this->foreverKey(), 0, -1)));
	}

	/**
	 * Get the forever list identifier.
	 *
	 * @return string
	 */
	protected function foreverKey()
	{
		return $this->tags->tagKey($this->name).':forever';
	}

	/**
	 * Get the cache key prefix.
	 *
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->store->getPrefix();
	}

}