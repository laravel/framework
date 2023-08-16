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
        $this->tags->addEntry(
            $this->itemKey($key),
            ! is_null($ttl) ? $this->getSeconds($ttl) : 0
        );

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

        $this->tags->addEntry(
            $this->itemKey($key),
            $this->getSeconds($ttl)
        );

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
        /** @var \Illuminate\Cache\RedisStore $redis_store */
        $redis_store = $this->store;

        $redis_connection = $redis_store->connection();
        $redis_prefix = match ($redis_connection::class) {
            \Illuminate\Redis\Connections\PhpRedisConnection::class => $redis_connection->client()->getOption(\Redis::OPT_PREFIX),
            \Illuminate\Redis\Connections\PredisConnection::class => $redis_connection->client()->getOptions()->prefix,
        };

        /** @var \Illuminate\Cache\RedisTagSet $redis_tag_set */
        $redis_tag_set = $this->tags;
        $entries = $redis_tag_set->entries();

        $cache_tags = [];
        $need_deleted_keys = [];

        $cache_prefix = $redis_prefix.$redis_store->getPrefix();

        foreach ($this->tags->getNames() as $name) {
            $cache_tags[] = $cache_prefix.$this->tags->tagId($name);
        }

        foreach ($entries as $entry) {
            $need_deleted_keys[] = $redis_store->getPrefix().$entry;
        }

        $lua_script = <<<'LUA'
            local prefix = table.remove(ARGV, 1)
            for i, key in ipairs(KEYS) do
                redis.call('DEL', key)
                for j, arg in ipairs(ARGV) do
                    local zkey = string.gsub(key, prefix, "")
                    redis.call('ZREM', arg, zkey)
                end
            end
            LUA;

        $redis_connection->eval($lua_script, count($need_deleted_keys), ...$need_deleted_keys, ...[$cache_prefix, ...$cache_tags]);

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
