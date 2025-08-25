<?php

namespace Illuminate\Cache;

class FileLock extends CacheLock
{
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
