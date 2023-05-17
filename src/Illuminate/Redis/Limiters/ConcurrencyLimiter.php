<?php

namespace Illuminate\Redis\Limiters;

use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Redis\Lua\LuaScript;
use Illuminate\Redis\Lua\LuaScriptArguments;
use Illuminate\Support\Str;
use Throwable;

class ConcurrencyLimiter
{
    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    protected $redis;

    /**
     * The name of the limiter.
     *
     * @var string
     */
    protected $name;

    /**
     * The allowed number of concurrent tasks.
     *
     * @var int
     */
    protected $maxLocks;

    /**
     * The number of seconds a slot should be maintained.
     *
     * @var int
     */
    protected $releaseAfter;

    /**
     * Create a new concurrency limiter instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $redis
     * @param  string  $name
     * @param  int  $maxLocks
     * @param  int  $releaseAfter
     * @return void
     */
    public function __construct($redis, $name, $maxLocks, $releaseAfter)
    {
        $this->name = $name;
        $this->redis = $redis;
        $this->maxLocks = $maxLocks;
        $this->releaseAfter = $releaseAfter;
    }

    /**
     * Attempt to acquire the lock for the given number of seconds.
     *
     * @param  int  $timeout
     * @param  callable|null  $callback
     * @param  int  $sleep
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Redis\LimiterTimeoutException
     * @throws \Throwable
     */
    public function block($timeout, $callback = null, $sleep = 250)
    {
        $starting = time();

        $id = Str::random(20);

        while (! $slot = $this->acquire($id)) {
            if (time() - $timeout >= $starting) {
                throw new LimiterTimeoutException;
            }

            usleep($sleep * 1000);
        }

        if (is_callable($callback)) {
            try {
                return tap($callback(), function () use ($slot, $id) {
                    $this->release($slot, $id);
                });
            } catch (Throwable $exception) {
                $this->release($slot, $id);

                throw $exception;
            }
        }

        return true;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @param  string  $id  A unique identifier for this lock
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Redis\LuaScriptExecuteException
     */
    protected function acquire($id)
    {
        $slots = array_map(function ($i) {
            return $this->name.$i;
        }, range(1, $this->maxLocks));

        return $this->redis->lua()
            ->execute($this->lockScript(), LuaScriptArguments::with($slots, [$this->name, $this->releaseAfter, $id]))
            ->getResult();
    }

    /**
     * Get the Lua script for acquiring a lock.
     *
     * KEYS    - The keys that represent available slots
     * ARGV[1] - The limiter name
     * ARGV[2] - The number of seconds the slot should be reserved
     * ARGV[3] - The unique identifier for this lock
     *
     * @return \Illuminate\Redis\Lua\LuaScript
     */
    protected function lockScript()
    {
        return LuaScript::fromPlainScript(<<<'LUA'
for index, value in pairs(redis.call('mget', unpack(KEYS))) do
    if not value then
        redis.call('set', KEYS[index], ARGV[3], "EX", ARGV[2])
        return ARGV[1]..index
    end
end
LUA);
    }

    /**
     * Release the lock.
     *
     * @param string $key
     * @param string $id
     * @return void
     *
     * @throws \Illuminate\Contracts\Redis\LuaScriptExecuteException
     */
    protected function release($key, $id)
    {
        $this->redis->lua()
            ->execute($this->releaseScript(),LuaScriptArguments::with([$key],[$id]))
            ->throwIfError();
    }

    /**
     * Get the Lua script to atomically release a lock.
     *
     * KEYS[1] - The name of the lock
     * ARGV[1] - The unique identifier for this lock
     *
     * @return \Illuminate\Redis\Lua\LuaScript
     */
    protected function releaseScript()
    {
        return LuaScript::fromPlainScript(<<<'LUA'
if redis.call('get', KEYS[1]) == ARGV[1]
then
    return redis.call('del', KEYS[1])
else
    return 0
end
LUA);
    }
}
