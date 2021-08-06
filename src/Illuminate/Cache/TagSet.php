<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;

class TagSet
{
    /**
     * The cache store implementation.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $store;

    /**
     * The tag names.
     *
     * @var array
     */
    protected $names = [];

    /**
     * Create a new TagSet instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @param  array  $names
     * @return void
     */
    public function __construct(Store $store, array $names = [])
    {
        $this->store = $store;
        $this->names = $names;
    }

    /**
     * Reset all tags in the set.
     *
     * @return void
     */
    public function reset()
    {
        array_walk($this->names, [$this, 'resetTag']);
    }

    /**
     * Reset the tag and return the new tag identifier.
     *
     * @param  string  $name
     * @return string
     */
    public function resetTag($name)
    {
        $this->store->forever($this->tagKey($name), $id = str_replace('.', '', uniqid('', true)));

        return $id;
    }

    /**
     * Get a unique namespace that changes when any of the tags are flushed.
     *
     * @return string
     */
    public function getNamespace()
    {
        return implode('|', $this->tagIds());
    }

    /**
     * Get an array of tag identifiers for all of the tags in the set.
     *
     * @return array
     */
    protected function tagIds()
    {
        return $this->setMissingTagIds($this->store->many(array_map([$this, 'tagKey'], $this->names)));
    }

    /**
     * Finds and sets missing tag ids
     *
     * @param array $tagIds
     * @return array
     */
    protected function setMissingTagIds($tagIds)
    {
        $missingTagIds = [];
        foreach ($tagIds as $key => $value) {
            if (is_null($value)) {
                $missingTagIds[] = $key;
            }
        }

        if (! count($missingTagIds) || ! ($setTagIds = $this->resetTags($missingTagIds))) {
            return $tagIds;
        }

        return array_merge($tagIds, $setTagIds);
    }

    /**
     * Resets multiple tags and once and returns the new identifier.
     */
    protected function resetTags($tagIds)
    {
        $result = [];
        foreach ($tagIds as $tagId) {
            if ($this->store->forever($tagId, $id = str_replace('.', '', uniqid('', true)))) {
                $result[$tagId] = $id;
            }
        }

        return $result;
    }

    /**
     * Get the unique tag identifier for a given tag.
     *
     * @param  string  $name
     * @return string
     */
    public function tagId($name)
    {
        return $this->store->get($this->tagKey($name)) ?: $this->resetTag($name);
    }

    /**
     * Get the tag identifier key for a given tag.
     *
     * @param  string  $name
     * @return string
     */
    public function tagKey($name)
    {
        return 'tag:'.$name.':key';
    }

    /**
     * Get all of the tag names in the set.
     *
     * @return array
     */
    public function getNames()
    {
        return $this->names;
    }
}
