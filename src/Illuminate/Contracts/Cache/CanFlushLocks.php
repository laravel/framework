<?php

namespace Illuminate\Contracts\Cache;

interface CanFlushLocks
{
    /**
     * Flush all locks managed by the store.
     *
     * @return bool
     */
    public function flushLocks(): bool;

    /**
     * Determine if the lock store is separate from the cache store.
     *
     * @return bool
     */
    public function hasSeparateLockStore(): bool;
}
