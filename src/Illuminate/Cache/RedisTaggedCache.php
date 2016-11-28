<?php

namespace Illuminate\Cache;

class RedisTaggedCache extends TaggedCache
{
    /**
     * Forever reference key.
     *
     * @var string
     */
    const REFERENCE_KEY_FOREVER = 'forever_ref';
    /**
     * Standard reference key.
     *
     * @var string
     */
    const REFERENCE_KEY_STANDARD = 'standard_ref';

    /**
     * Store an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  \DateTime|float|int  $minutes
     * @return void
     */
    public function put($key, $value, $minutes = null)
    {
        $this->pushStandardKeys($this->tags->getNamespace(), $key);

        parent::put($key, $value, $minutes);
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
        $this->pushForeverKeys($this->tags->getNamespace(), $key);

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
        $this->deleteStandardKeys();

        parent::flush();
    }

    /**
     * Store standard key references into store.
     *
     * @param  string  $namespace
     * @param  string  $key
     * @return void
     */
    protected function pushStandardKeys($namespace, $key)
    {
        $this->pushKeys($namespace, $key, self::REFERENCE_KEY_STANDARD);
    }

    /**
     * Store forever key references into store.
     *
     * @param  string  $namespace
     * @param  string  $key
     * @return void
     */
    protected function pushForeverKeys($namespace, $key)
    {
        $this->pushKeys($namespace, $key, self::REFERENCE_KEY_FOREVER);
    }

    /**
     * Store a reference to the cache key against the reference key.
     *
     * @param  string  $namespace
     * @param  string  $key
     * @param  string  $reference
     * @return void
     */
    protected function pushKeys($namespace, $key, $reference)
    {
        $fullKey = $this->store->getPrefix().sha1($namespace).':'.$key;

        foreach (explode('|', $namespace) as $segment) {
            $this->store->connection()->sadd($this->referenceKey($segment, $reference), $fullKey);
        }
    }

    /**
     * Delete all of the items that were stored forever.
     *
     * @return void
     */
    protected function deleteForeverKeys()
    {
        $this->deleteKeysByReference(self::REFERENCE_KEY_FOREVER);
    }

    /**
     * Delete all standard items.
     *
     * @return void
     */
    protected function deleteStandardKeys()
    {
        $this->deleteKeysByReference(self::REFERENCE_KEY_STANDARD);
    }

    /**
     * Find and delete all of the items that were stored against a reference.
     *
     * @param  string  $reference
     * @return void
     */
    protected function deleteKeysByReference($reference)
    {
        foreach (explode('|', $this->tags->getNamespace()) as $segment) {
            $this->deleteValues($segment = $this->referenceKey($segment, $reference));

            $this->store->connection()->del($segment);
        }
    }

    /**
     * Delete item keys that have been stored against a reference.
     *
     * @param  string  $referenceKey
     * @return void
     */
    protected function deleteValues($referenceKey)
    {
        $values = array_unique($this->store->connection()->smembers($referenceKey));

        if (count($values) > 0) {
            foreach (array_chunk($values, 1000) as $valuesChunk) {
                call_user_func_array([$this->store->connection(), 'del'], $valuesChunk);
            }
        }
    }

    /**
     * Get the reference key for the segment.
     *
     * @param  string  $segment
     * @param  string  $suffix
     * @return string
     */
    protected function referenceKey($segment, $suffix)
    {
        return $this->store->getPrefix().$segment.':'.$suffix;
    }
}
