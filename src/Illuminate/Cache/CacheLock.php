<?php

namespace Illuminate\Cache;

class CacheLock extends Lock
{
    /**
     * The cache store implementation.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $store;

    /**
     * Create a new lock instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return void
     */
    public function __construct($store, $name, $seconds, $owner = null)
    {
        parent::__construct($name, $seconds, $owner);

        $this->store = $store;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return bool
     */
    public function acquire()
    {
        // Cache stores that return this lock type must ensure that they
        // contain a compatible add method that atomically stores an item
        // in the cache if does not already exist.
        return $this->store->add(
            $this->name, $this->owner, $this->seconds
        );
    }

    /**
     * Release the lock.
     *
     * @return bool
     */
    public function release()
    {
        if ($this->isOwnedByCurrentProcess()) {
            return $this->store->forget($this->name);
        }

        return false;
    }

    /**
     * Releases this lock regardless of ownership.
     *
     * @return void
     */
    public function forceRelease()
    {
        $this->store->forget($this->name);
    }

    /**
     * Returns the owner value written into the driver for this lock.
     *
     * @return mixed
     */
    protected function getCurrentOwner()
    {
        return $this->store->get($this->name);
    }
}
