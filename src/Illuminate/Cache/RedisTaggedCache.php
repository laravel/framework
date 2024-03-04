<?php

namespace Illuminate\Cache;

class RedisTaggedCache extends TaggedCache
{
    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return bool
     */
    public function add($key, $value, $ttl = null)
    {
        $seconds = null;

        if ($ttl !== null) {
            $seconds = $this->getSeconds($ttl);

            if ($seconds > 0) {
                $this->tags->addEntry(
                    $this->itemKey($key),
                    $seconds
                );
            }
        }

        return parent::add($key, $value, $ttl);
    }

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
        if (is_null($ttl)) {
            return $this->forever($key, $value);
        }

        $seconds = $this->getSeconds($ttl);

        if ($seconds > 0) {
            $this->tags->addEntry(
                $this->itemKey($key),
                $seconds
            );
        }

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
        $this->tags->addEntry($this->itemKey($key), updateWhen: 'NX');

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
        $this->tags->addEntry($this->itemKey($key), updateWhen: 'NX');

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
        $this->tags->addEntry($this->itemKey($key));

        return parent::forever($key, $value);
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        $this->flushValues();
        $this->tags->flush();

        return true;
    }

    /**
     * Flush the individual cache entries for the tags.
     *
     * @return void
     */
    protected function flushValues()
    {
        $entries = $this->tags->entries()
            ->map(fn (string $key) => $this->store->getPrefix().$key)
            ->chunk(1000);

        foreach ($entries as $cacheKeys) {
            $this->store->connection()->del(...$cacheKeys);
        }
    }

    /**
     * Remove all stale reference entries from the tag set.
     *
     * @return bool
     */
    public function flushStale()
    {
        $this->tags->flushStaleEntries();

        return true;
    }
}
