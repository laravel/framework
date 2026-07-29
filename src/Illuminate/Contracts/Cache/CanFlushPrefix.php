<?php

namespace Illuminate\Contracts\Cache;

interface CanFlushPrefix
{
    /**
     * Flush all cache entries managed by the store's configured prefix.
     *
     * @return bool
     */
    public function flushPrefix(): bool;
}
