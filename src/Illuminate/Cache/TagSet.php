<?php namespace Illuminate\Cache;

class TagSet {

	/**
	 * The cache store implementation.
	 *
	 * @var \Illuminate\Cache\StoreInterface
	 */
	protected $store;

	/**
	 * The tag names.
	 *
	 * @var array
	 */
	protected $names = array();

	/**
	 * Create a new TagSet instance.
	 *
	 * @param  \Illuminate\Cache\StoreInterface  $store
	 * @param  string  $names
	 * @return void
	 */
	public function __construct(StoreInterface $store, $names)
	{
		$this->names = $names;
		$this->store = $store;
	}

	/**
	 * Reset all tags
	 *
	 * @return string
	 */
	public function reset()
	{
		foreach($this->names as $name)
			$this->resetTag($name);
	}

	/**
	 * Get the unique tag identifier.
	 *
	 * @param  string the name for a given tag
	 * @return string
	 */
	protected function tagId($name)
	{
		$id = $this->store->get($this->tagKey($name));

		if (is_null($id))
		{
			$id = $this->resetTag($name);
		}

		return $id;
	}

	/**
	 * get an array of tag identifiers for all tags
	 * @return array
	 */
	protected function tagIds()
	{
		$ids = array();
		foreach ($this->names as $name)
			$ids[] = $this->tagId($name);
		return $ids;
	}

	/**
	 * get a unique namespace that will change if any of the tags are flushed
	 * @return string
	 */
	public function getNamespace()
	{
		//The sha1 ensures that the namespace is not too long, but is otherwise unnecessary
		return sha1(implode('|', $this->tagIds()));
	}

	/**
	 * Reset the tag, returning a new tag identifier
	 *
	 * @param string $name
	 * @return string
	 */
	protected function resetTag($name)
	{
		$this->store->forever($this->tagKey($name), $id = uniqid());

		return $id;
	}

	/**
	 * Get the tag identifier key for a given tag.
	 *
	 * @param string $name
	 * @return string
	 */
	public function tagKey($name)
	{
		return 'tag:'.$name.':key';
	}

}