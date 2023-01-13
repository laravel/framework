<?php

namespace Illuminate\Queue;

use Illuminate\Bus\UniqueLock;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;

trait UniqueAware
{
    /**
     * Check if the current job can be dispatched based on its uniqueness
     *
     * @return boolean
     */
    public function isLocked()
    {
        return Container::getInstance()->make(Repository::class)->isLocked($this->getLockCacheKey());
    }

    /**
     * Return the lock cache key for the current job
     *
     * @return string
     */
    public function getLockCacheKey(): string
    {
        $lock = new UniqueLock(Container::getInstance()->make(Repository::class));

        return $lock->getKey($this);
    }
}
