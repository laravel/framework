<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Lock as LockContract;

class MemcachedLock extends Lock implements LockContract
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
     * @return void
     */
    public function __construct($memcached, $name, $seconds)
    {
        parent::__construct($name, $seconds);

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
            $this->name, 1, $this->seconds
        );
    }

    /**
     * Release the lock.
     *
     * @return void
     */
    public function release()
    {
        $this->memcached->delete($this->name);
    }
}
