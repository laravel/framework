<?php

namespace Illuminate\Cache;

class MemcachedLock extends Lock
{
    /**
     * The Memcached instance.
     *
     * @var \Memcached
     */
    protected $memcached;

    /**
     * Create a new lock instance.
     *
     * @param  \Memcached  $memcached
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return void
     */
    public function __construct($memcached, $name, $seconds, $owner = null)
    {
        parent::__construct($name, $seconds, $owner);

        $this->memcached = $memcached;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return bool
     */
    public function acquire()
    {
        return $this->memcached->add(
            $this->name, $this->owner, $this->seconds
        );
    }

    /**
     * Release the lock.
     *
     * @return void
     */
    public function release()
    {
        if ($this->isOwnedByCurrentProcess()) {
            $this->memcached->delete($this->name);
        }
    }

    /**
     * Releases this lock in disregard of ownership.
     *
     * @return void
     */
    public function forceRelease()
    {
        $this->memcached->delete($this->name);
    }

    /**
     * Returns the owner value written into the driver for this lock.
     *
     * @return mixed
     */
    protected function getCurrentOwner()
    {
        return $this->memcached->get($this->name);
    }
}
