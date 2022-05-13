<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Repository;

/**
 * @template TValue
 */
class RefreshOperation
{
    /**
     * The cache repository to use to refresh the item.
     *
     * @var \Illuminate\Contracts\Cache\Repository|\Illuminate\Contracts\Cache\LockProvider
     */
    protected $cache;

    /**
     * The key of the cached item.
     *
     * @var string
     */
    protected $key;

    /**
     * Seconds to hold the lock and release it, if any.
     *
     * @var int
     */
    protected $seconds = 0;

    /**
     * Lifetime of the item.
     *
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    protected $ttl;

    /**
     * The name of the lock.
     *
     * @var string
     */
    protected $name;

    /**
     * The owner of the lock, if any.
     *
     * @var string|null
     */
    protected $owner;

    /**
     * How much time to wait for the lock to release.
     *
     * @var int
     */
    protected $wait = 10;

    /**
     * The refresh operation.
     *
     * @var callable
     */
    protected $callback;

    /**
     * Create a new refresh operation instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $repository
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    public function __construct(Repository $repository, string $key, $ttl)
    {
        $this->cache = $repository;
        $this->key = $key;
        $this->ttl = $ttl;

        $this->name = $this->key.':refresh';
    }

    /**
     * Changes cache lock configuration.
     *
     * @param  string  $name
     * @param  int|null  $seconds
     * @param  string|null  $owner
     * @return $this
     */
    public function lock($name, $seconds = null, $owner = null)
    {
        [$this->name, $this->seconds, $this->owner] = [$name, $seconds ?? $this->seconds, $owner ?? $this->owner];

        return $this;
    }

    /**
     * Sets the seconds to wait to acquire the lock.
     *
     * @param  int  $seconds
     * @return $this
     */
    public function waitFor($seconds)
    {
        $this->wait = $seconds;

        return $this;
    }

    /**
     * Retrieves and refreshes the item from the cache through a callback.
     *
     * @param  callable<TValue|mixed>  $callback
     * @return TValue|mixed
     */
    public function put(callable $callback)
    {
        $this->callback = $callback;

        return $this->cache
            ->lock($this->name, $this->seconds, $this->owner)
            ->block($this->wait, function () {
                return $this->refresh();
            });
    }

    /**
     * Executes the refresh operation.
     *
     * @return TValue|mixed
     */
    protected function refresh()
    {
        $expire = $this->expireObject();

        $item = $this->cache->get($this->key);

        $result = ($this->callback)($item, $expire);

        $exists = ! is_null($item);

        return tap($result, function ($result) use ($expire, $exists) {
            // We will call the cache store only on two cases: when this callback returns
            // something to be put, and when there is something to forget in the cache.
            // This way we can save a cache call when there is nothing to manipulate.
            if (($exists && $expire->at === 0) || !is_null($result)) {
                $this->cache->put($this->key, $result, $expire->at);
            }
        });
    }

    /**
     * Creates a simple object to manage the item lifetime.
     *
     * @return object
     */
    protected function expireObject()
    {
        return new class($this->ttl)
        {
            public $at;

            public function __construct($at)
            {
                $this->at($at);
            }

            public function at($at)
            {
                $this->at = $at;
            }

            public function in($at)
            {
                $this->at($at);
            }

            public function now()
            {
                $this->at(0);
            }

            public function never()
            {
                $this->at(null);
            }
        };
    }
}
