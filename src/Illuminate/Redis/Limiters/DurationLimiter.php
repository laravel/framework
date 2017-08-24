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
    private $maxLocks;

    /**
     * The number of seconds a slot should be maintained.
     *
     * @var int
     */
    private $seconds;

    /**
     * Create a new duration limiter instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection $redis
     * @param  string $name
     * @param  int $maxLocks
     * @param  int $seconds
     * @return void
     */
    public function __construct($redis, $name, $maxLocks, $seconds)
    {
        $this->name = $name;
        $this->redis = $redis;
        $this->seconds = $seconds;
        $this->maxLocks = $maxLocks;
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
            if ($this->seconds > 1 || time() - $timeout >= $starting) {
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
    protected function acquire()
    {
        return $this->redis->eval($this->luaScript(), 1,
            $this->name, microtime(true), time(), $this->seconds, $this->maxLocks
        );
    }

    /**
     * Get the Lua script for acquiring a lock.
     *
     * KEYS[1] - The limiter name
     * ARGV[1] - Current time in microseconds
     * ARGV[2] - Current time in seconds
     * ARGV[3] - Duration of the bucket
     * ARGV[4] - Allowed number of tasks
     *
     * @return string
     */
    protected function luaScript()
    {
        return <<<'LUA'
local function reset()
    redis.call('HMSET', KEYS[1], 'start', ARGV[2], 'end', ARGV[2] + ARGV[3], 'count', 1)
    return redis.call('EXPIRE', KEYS[1], ARGV[3] * 2)
end

if redis.call('EXISTS', KEYS[1]) == 0 then
    return reset()
end

if ARGV[1] >= redis.call('HGET', KEYS[1], 'start') and ARGV[1] <= redis.call('HGET', KEYS[1], 'end') then
    return tonumber(redis.call('HINCRBY', KEYS[1], 'count', 1)) <= tonumber(ARGV[4])
end

return reset()
LUA;
    }
}
