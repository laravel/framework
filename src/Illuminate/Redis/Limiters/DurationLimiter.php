<?php

namespace Illuminate\Redis\Limiters;

use Illuminate\Contracts\Redis\LimiterTimeoutException;

class DurationLimiter
{
    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    private $redis;

    /**
     * The unique name of the lock.
     *
     * @var string
     */
    private $name;

    /**
     * The allowed number of concurrent tasks.
     *
     * @var int
     */
    private $size;

    /**
     * The number of seconds a slot should be maintained.
     *
     * @var int
     */
    private $duration;

    /**
     * Create a new concurrency lock instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection $redis
     * @param  string $name
     * @param  int $size
     * @param  int $duration
     * @return void
     */
    public function __construct($redis, $name, $size, $duration)
    {
        $this->name = $name;
        $this->size = $size;
        $this->duration = $duration;
        $this->redis = $redis;
    }

    /**
     * Attempt to acquire the lock for the given number of seconds.
     *
     * @param  int $timeout
     * @param  callable|null $callback
     * @return bool
     * @throws \Illuminate\Contracts\Redis\LimiterTimeoutException
     */
    public function block($timeout, $callback = null)
    {
        $starting = time();

        while (! $this->acquire()) {
            if ($this->duration > 1 || time() - $timeout >= $starting) {
                throw new LimiterTimeoutException;
            }

            usleep(750 * 1000);
        }

        if (is_callable($callback)) {
            $callback();
        }

        return true;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return mixed
     */
    private function acquire()
    {
        return $this->redis->eval($this->luaScript(), 1,
            $this->name, microtime(true), time(), $this->duration, $this->size
        );
    }

    /**
     * Get the Lua script for acquiring a lock.
     *
     * KEYS[1] - The lock name
     * ARGV[1] - Current time in microseconds
     * ARGV[2] - Current time in seconds
     * ARGV[3] - Duration
     * ARGV[4] - Limit
     *
     * @return string
     */
    private function luaScript()
    {
        return <<<'LUA'
local endTime = ARGV[2] + ARGV[3]

if redis.call('EXISTS', KEYS[1]) == 0 then
    redis.call('HMSET', KEYS[1], 'start', ARGV[2], 'end', endTime, 'count', 1)
    redis.call('EXPIRE', KEYS[1], ARGV[3] * 2)
    return 1
end

if ARGV[1] >= redis.call('HGET', KEYS[1], 'start') and ARGV[1] <= redis.call('HGET', KEYS[1], 'end') then
    return tonumber(redis.call('HINCRBY', KEYS[1], 'count', 1)) <= tonumber(ARGV[4])
end

redis.call('HMSET', KEYS[1], 'start', ARGV[2], 'end', endTime, 'count', 1)
redis.call('EXPIRE', KEYS[1], ARGV[3] * 2)
return 1
LUA;
    }
}
