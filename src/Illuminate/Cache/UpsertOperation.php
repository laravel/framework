<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;

/**
 * @template TValue
 */
class UpsertOperation
{
    /**
     * The cache repository to use for upserting.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The key to use when upserting the value.
     *
     * @var string
     */
    protected $key;

    /**
     * How much time to hold the lock and release it.
     *
     * @var int
     */
    protected $lock = 15;

    /**
     * How much time to wait for the lock to release.
     *
     * @var int
     */
    protected $wait;

    /**
     * The owner of the lock.
     *
     * @var string|null
     */
    protected $owner;

    /**
     * The upsert operation.
     *
     * @var callable
     */
    protected $callback;

    /**
     * Create a new upsert operation instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $repository
     * @param  string  $key
     */
    public function __construct(Repository $repository, string $key)
    {
        $this->cache = $repository;
        $this->key = $key;
    }

    /**
     * Locks the key for a given amount of time.
     *
     * @param  int  $lock
     * @return $this
     */
    public function lockBy($lock)
    {
        $this->lock = $lock;

        return $this;
    }

    /**
     * Sets the amount of time to wait for the lock.
     *
     * @param  int  $wait
     * @return $this
     */
    public function waitFor($wait)
    {
        $this->wait = $wait;

        return $this;
    }

    /**
     * Sets the owner of the upsert lock.
     *
     * @param  string  $owner
     * @return $this
     */
    public function ownedBy($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Upserts the item from the cache using a callback, saving its result back into the cache.
     *
     * @param  callable<TValue|mixed>  $callback
     * @return TValue|mixed
     */
    public function upsert(callable $callback)
    {
        $this->callback = $callback;

        if ($this->cache->getStore() instanceof LockProvider) {
            return $this->putWithLock();
        }

        return $this->put();
    }

    /**
     * Executes the upsert operation using a lock.
     *
     * @return TValue|mixed
     */
    protected function putWithLock()
    {
        $lock = $this->cache->getStore()->lock($this->key.':laravel-upsert', $this->lock, $this->owner);

        return $lock->block($this->wait ?? $this->lock, function () {
            return $this->put();
        });
    }

    /**
     * Executes the upsert operation.
     *
     * @return TValue|mixed
     */
    protected function put()
    {
        $expire = $this->expireObject();

        $result = ($this->callback)($this->cache->get($this->key), $expire);

        return tap($result, function ($result) use ($expire) {
            if (!is_null($result)) {
                $this->cache->put($this->key, $result, $expire->at);
            }
        });
    }

    /**
     * Creates a simple object to hold expiration data.
     *
     * @return object
     */
    protected function expireObject()
    {
        return new class(null)
        {
            public function __construct(public $at)
            {
                //
            }

            public function never()
            {
                $this->at = null;
            }

            public function at($at)
            {
                $this->at = $at;
            }

            public function now()
            {
                $this->at = 0;
            }
        };
    }
}
