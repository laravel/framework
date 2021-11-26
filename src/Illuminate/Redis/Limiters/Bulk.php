<?php

namespace Illuminate\Redis\Limiters;

class Bulk
{
    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    protected $redis;

    /**
     * The name of the cache Key.
     *
     * @var string
     */
    protected $name;

    /**
     * The item.
     *
     * @var string
     */
    protected $item;

    /**
     * The maximum number of items that can be cache.
     *
     * @var int
     */
    protected $maxItemCount;

    /**
     * force to release the cache.
     *
     * @var false|mixed
     */
    protected $forceRelease;

    /**
     * Create a new bulk instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $redis
     * @param  string  $name
     * @param  string  $item
     * @param  int  $maxItemCount
     * @param  bool  $forceRelease
     */
    public function __construct($redis, $name, $item, $maxItemCount, $forceRelease)
    {
        $this->redis = $redis;
        $this->name = $name;
        $this->item = $item;
        $this->maxItemCount = $maxItemCount;
        $this->forceRelease = $forceRelease;
    }

    /**
     * Decide to store items in cache or release them according to cache size or ForceRelease.
     *
     * @param  callable  $callable
     */
    public function then($callable)
    {
        if ($this->isCacheFull() || $this->forceRelease) {
            $this->bulkFromCache($callable, $this->item);
        } else {
            $this->addToCache($this->item);
        }
    }

    /**
     * Detect cache is full or not, cached items + current item >= maxItem.
     *
     * @return bool
     */
    private function isCacheFull(): bool
    {
        return $this->redis->command('lLen', [$this->name]) >= $this->maxItemCount - 1;
    }

    /**
     * add item to cache.
     *
     * @param  string  $item
     */
    private function addToCache($item)
    {
        $this->redis->command('rpush', [$this->name, $item]);
    }

    /**
     * Get items from the cache and add the current item to them and return with an ordinary sort.
     *
     * @param  callable  $callable
     * @param  string|null  $item
     */
    private function bulkFromCache($callable, $item = null)
    {
        $items = $this->redis->command('lrange', [$this->name, 0, $this->maxItemCount]);

        if ($item) {
            $items[count($items)] = $item;
        }

        $this->redis->command('del', [$this->name]);

        $callable($items);
    }
}
