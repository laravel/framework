<?php

namespace Illuminate\Cache;

use Illuminate\Support\Carbon;
use Illuminate\Support\LazyCollection;

class RedisTagSet extends TagSet
{
    /**
     * Add a reference entry to the tag set's underlying sorted set.
     *
     * @param  string  $key
     * @param  int|null  $ttl
     * @param  string  $updateWhen
     * @return void
     */
    public function addEntry(string $key, ?int $ttl = null, $updateWhen = null)
    {
        $ttl = is_null($ttl) ? -1 : Carbon::now()->addSeconds($ttl)->getTimestamp();

        foreach ($this->tagIds() as $tagKey) {
            if ($updateWhen) {
                $this->store->connection()->zadd($this->store->getPrefix().$tagKey, $updateWhen, $ttl, $key);
            } else {
                $this->store->connection()->zadd($this->store->getPrefix().$tagKey, $ttl, $key);
            }
        }
    }

    /**
     * Get all of the cache entry keys for the tag set.
     *
     * @return \Illuminate\Support\LazyCollection
     */
    public function entries()
    {
        return LazyCollection::make(function () {
            foreach ($this->tagIds() as $tagKey) {
                $cursor = $defaultCursorValue = '0';

                do {
                    [$cursor, $entries] = $this->store->connection()->zscan(
                        $this->store->getPrefix().$tagKey,
                        $cursor,
                        ['match' => '*', 'count' => 1000]
                    );

                    if (! is_array($entries)) {
                        break;
                    }

                    $entries = array_unique(array_keys($entries));

                    if (count($entries) === 0) {
                        continue;
                    }

                    foreach ($entries as $entry) {
                        yield $entry;
                    }
                } while (((string) $cursor) !== $defaultCursorValue);
            }
        });
    }

    /**
     * Remove the stale entries from the tag set.
     *
     * @return void
     */
    public function flushStaleEntries()
    {
        $this->store->connection()->pipeline(function ($pipe) {
            foreach ($this->tagIds() as $tagKey) {
                $pipe->zremrangebyscore($this->store->getPrefix().$tagKey, 0, Carbon::now()->getTimestamp());
            }
        });
    }

    /**
     * Flush the tag from the cache.
     *
     * @param  string  $name
     */
    public function flushTag($name)
    {
        return $this->resetTag($name);
    }

    /**
     * Reset the tag and return the new tag identifier.
     *
     * @param  string  $name
     * @return string
     */
    public function resetTag($name)
    {
        $this->store->forget($this->tagKey($name));

        return $this->tagId($name);
    }

    /**
     * Get the unique tag identifier for a given tag.
     *
     * @param  string  $name
     * @return string
     */
    public function tagId($name)
    {
        return "tag:{$name}:entries";
    }

    /**
     * Get the tag identifier key for a given tag.
     *
     * @param  string  $name
     * @return string
     */
    public function tagKey($name)
    {
        return "tag:{$name}:entries";
    }
}
