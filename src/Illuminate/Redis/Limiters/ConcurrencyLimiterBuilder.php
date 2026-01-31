<?php

namespace Illuminate\Redis\Limiters;

use Illuminate\Cache\Limiters\ConcurrencyLimiterBuilder as BaseConcurrencyLimiterBuilder;
use Illuminate\Contracts\Redis\LimiterTimeoutException;

class ConcurrencyLimiterBuilder extends BaseConcurrencyLimiterBuilder
{
    /**
     * Execute the given callback if a lock is obtained, otherwise call the failure callback.
     *
     * @param  callable  $callback
     * @param  callable|null  $failure
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Redis\LimiterTimeoutException
     */
    public function then(callable $callback, ?callable $failure = null)
    {
        try {
            return $this->createLimiter()->block($this->timeout, $callback, $this->sleep);
        } catch (LimiterTimeoutException $e) {
            if ($failure) {
                return $failure($e);
            }

            throw $e;
        }
    }

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
