<?php

namespace Illuminate\Redis\Limiters;

use Illuminate\Cache\Limiters\ConcurrencyLimiter as BaseConcurrencyLimiter;
use Illuminate\Contracts\Redis\LimiterTimeoutException;

class ConcurrencyLimiter extends BaseConcurrencyLimiter
{
    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    protected $redis;

    /**
     * Create a new concurrency limiter instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $redis
     * @param  string  $name
     * @param  int  $maxLocks
     * @param  int  $releaseAfter
     */
    public function __construct($redis, $name, $maxLocks, $releaseAfter)
    {
        $this->name = $name;
        $this->redis = $redis;
        $this->maxLocks = $maxLocks;
        $this->releaseAfter = $releaseAfter;
    }

    /**
     * Create a new limiter timeout exception.
     *
     * @return \Illuminate\Contracts\Redis\LimiterTimeoutException
     */
    protected function newTimeoutException()
    {
        return new LimiterTimeoutException;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @param  string  $id  A unique identifier for this lock
     * @return mixed
     */
    protected function acquire($id)
    {
        $slots = array_map(function ($i) {
            return $this->name.$i;
        }, range(1, $this->maxLocks));

        return $this->redis->eval(...array_merge(
            [$this->lockScript(), count($slots)],
            array_merge($slots, [$this->name, $this->releaseAfter, $id])
        ));
    }

    /**
     * Get the Lua script for acquiring a lock.
     *
     * KEYS    - The keys that represent available slots
     * ARGV[1] - The limiter name
     * ARGV[2] - The number of seconds the slot should be reserved
     * ARGV[3] - The unique identifier for this lock
     *
     * @return string
     */
    protected function lockScript()
    {
        return <<<'LUA'
for index, value in pairs(redis.call('mget', unpack(KEYS))) do
    if not value then
        redis.call('set', KEYS[index], ARGV[3], "EX", ARGV[2])
        return ARGV[1]..index
    end
end
LUA;
    }

    /**
     * Release the lock.
     *
     * @param  string  $key
     * @param  string  $id
     * @return void
     */
    protected function release($key, $id)
    {
        $this->redis->eval($this->releaseScript(), 1, $key, $id);
    }

    /**
     * Get the Lua script to atomically release a lock.
     *
     * KEYS[1] - The name of the lock
     * ARGV[1] - The unique identifier for this lock
     *
     * @return string
     */
    protected function releaseScript()
    {
        return <<<'LUA'
if redis.call('get', KEYS[1]) == ARGV[1]
then
    return redis.call('del', KEYS[1])
else
    return 0
end
LUA;
    }
}
