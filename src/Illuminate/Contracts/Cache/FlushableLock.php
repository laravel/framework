<?php

namespace Illuminate\Contracts\Cache;

interface FlushableLock
{
    /**
     * Flush all locks managed by the store.
     *
     * @return bool
     */
    public function flushLocks(): bool;
}
