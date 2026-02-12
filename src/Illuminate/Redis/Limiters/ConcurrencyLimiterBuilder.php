<?php

namespace Illuminate\Redis\Limiters;

use Illuminate\Cache\Limiters\ConcurrencyLimiterBuilder as BaseConcurrencyLimiterBuilder;

class ConcurrencyLimiterBuilder extends BaseConcurrencyLimiterBuilder
{
    /**
     * Create the concurrency limiter instance.
     *
     * @return \Illuminate\Redis\Limiters\ConcurrencyLimiter
     */
    protected function createLimiter()
    {
        return new ConcurrencyLimiter(
            $this->connection,
            $this->name,
            $this->maxLocks,
            $this->releaseAfter
        );
    }
}
