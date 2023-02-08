<?php

namespace Illuminate\Cache;

class FileLock extends CacheLock
{
    /**
     * The cache store implementation.
     *
     * @var FileStore
     */
    protected $store;

    /**
     * Create a new lock instance.
     *
     * @param  FileStore  $store
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return void
     */
    public function __construct(FileStore $store, $name, $seconds, $owner = null)
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
        return $this->store->add($this->name, $this->owner, $this->seconds);
    }
}
