<?php

namespace Illuminate\Redis\Limiters;

use Illuminate\Contracts\Redis\LimiterTimeoutException;

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
    protected $size;

    /**
     * The number of seconds a slot should be maintained.
     *
     * @var int
     */
    protected $age;

    /**
     * Create a new concurrency lock instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $redis
     * @param  string  $name
     * @param  int  $size
     * @param  int  $age
     * @return void
     */
    public function __construct($redis, $name, $size, $age)
    {
        $this->name = $name;
        $this->size = $size;
        $this->age = $age;
        $this->redis = $redis;
    }

    /**
     * Attempt to acquire the lock for the given number of seconds.
     *
     * @param  int  $timeout
     * @param  callable|null  $callback
     * @return bool
     * @throws \Illuminate\Contracts\Redis\LimiterTimeoutException
     */
    public function block($timeout, $callback = null)
    {
        $starting = time();

        while (! $slot = $this->acquire()) {
            if (time() - $timeout >= $starting) {
                throw new LimiterTimeoutException;
            }

            usleep(250 * 1000);
        }

        if (is_callable($callback)) {
            return tap($callback(), function () use ($slot) {
                $this->release($slot);
            });
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
        $slots = array_map(function ($i) {
            return $this->name.$i;
        }, range(1, $this->size));

        return $this->redis->eval($this->luaScript(), count($slots),
            ...array_merge($slots, [$this->name, $this->age])
        );
    }

    /**
     * Get the Lua script for acquiring a lock.
     *
     * KEYS    - The keys that represent available slots.
     * ARGV[1] - Lock name
     * ARGV[2] - Lock age in seconds
     *
     * @return string
     */
    private function luaScript()
    {
        return <<<'LUA'
for index, value in pairs(redis.call('mget', unpack(KEYS))) do
    if not value then 
        redis.call('set', ARGV[1]..index, "1", "EX", ARGV[2])
        return ARGV[1]..index
    end
end
LUA;
    }

    /**
     * Release the lock.
     *
     * @return void
     */
    protected function release($key)
    {
        $this->redis->del($key);
    }
}
