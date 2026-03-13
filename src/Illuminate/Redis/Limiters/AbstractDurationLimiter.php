<?php

namespace Illuminate\Redis\Limiters;

use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Support\Sleep;

abstract class AbstractDurationLimiter
{
    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    protected $redis;

    /**
     * The unique name of the lock.
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
    protected $decay;

    /**
     * The timestamp of the end of the current duration.
     *
     * @var int
     */
    public $decaysAt;

    /**
     * The number of remaining slots.
     *
     * @var int
     */
    public $remaining;

    /**
     * Create a new duration limiter instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $redis
     * @param  string  $name
     * @param  int  $maxLocks
     * @param  int  $decay
     */
    public function __construct($redis, $name, $maxLocks, $decay)
    {
        $this->name = $name;
        $this->decay = $decay;
        $this->redis = $redis;
        $this->maxLocks = $maxLocks;
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
     */
    public function block($timeout, $callback = null, $sleep = 750)
    {
        $starting = time();

        while (! $this->acquire()) {
            if (time() - $timeout >= $starting) {
                throw new LimiterTimeoutException;
            }

            Sleep::usleep($sleep * 1000);
        }

        if (is_callable($callback)) {
            return $callback();
        }

        return true;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return bool
     */
    public function acquire()
    {
        $results = $this->redis->eval(
            $this->luaScript(), 1, $this->name, microtime(true), time(), $this->decay, $this->maxLocks
        );

        $this->decaysAt = $results[1];

        $this->remaining = max(0, $results[2]);

        return (bool) $results[0];
    }

    /**
     * Determine if the key has been "accessed" too many times.
     *
     * @return bool
     */
    public function tooManyAttempts()
    {
        [$this->decaysAt, $this->remaining] = $this->redis->eval(
            $this->tooManyAttemptsLuaScript(), 1, $this->name, microtime(true), time(), $this->decay, $this->maxLocks
        );

        return $this->remaining <= 0;
    }

    /**
     * Clear the limiter.
     *
     * @return void
     */
    public function clear()
    {
        $this->redis->del($this->name);
    }

    /**
     * Get the Lua script for acquiring a lock.
     *
     * @return string
     */
    abstract protected function luaScript();

    /**
     * Get the Lua script to determine if the key has been "accessed" too many times.
     *
     * @return string
     */
    abstract protected function tooManyAttemptsLuaScript();
}
