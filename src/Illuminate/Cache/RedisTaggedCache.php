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
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return bool
     */
    public function put($key, $value, $ttl = null)
    {
        if ($ttl === null) {
            return $this->forever($key, $value);
        }

        $this->pushStandardKeys($this->tags->getNamespace(), $key);

        return parent::put($key, $value, $ttl);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        $this->pushStandardKeys($this->tags->getNamespace(), $key);

        return parent::increment($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        $this->pushStandardKeys($this->tags->getNamespace(), $key);

        return parent::decrement($key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        $this->pushForeverKeys($this->tags->getNamespace(), $key);

        return parent::forever($key, $value);
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        $this->deleteForeverKeys();
        $this->deleteStandardKeys();

        $this->tags->flush();

        return true;
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
        $cursor = $defaultCursorValue = '0';

        do {
            [$cursor, $valuesChunk] = $this->store->connection()->sscan(
                $referenceKey, $cursor, ['match' => '*', 'count' => 1000]
            );

            // PhpRedis client returns false if set does not exist or empty. Array destruction
            // on false stores null in each variable. If valuesChunk is null, it means that
            // there were not results from the previously executed "sscan" Redis command.
            if (is_null($valuesChunk)) {
                break;
            }

            $valuesChunk = array_unique($valuesChunk);

            if (count($valuesChunk) > 0) {
                $this->store->connection()->del(...$valuesChunk);
            }
        } while (((string) $cursor) !== $defaultCursorValue);
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
