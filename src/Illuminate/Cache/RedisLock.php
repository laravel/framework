<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Lock as LockContract;

class RedisLock extends Lock implements LockContract
{
    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    protected $redis;

    /**
     * The name of the lock.
     *
     * @var string
     */
    protected $name;

    /**
     * The number of seconds the lock should be maintained.
     *
     * @var int
     */
    protected $seconds;

    /**
     * Create a new lock instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $redis
     * @param  string  $name
     * @param  int  $seconds
     * @return void
     */
    public function __construct($redis, $name, $seconds)
    {
        $this->name = $name;
        $this->redis = $redis;
        $this->seconds = $seconds;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return bool
     */
    public function acquire()
    {
        $result = $this->redis->setnx($this->name, 1);

        if ($result === 1 && $this->seconds > 0) {
            $this->redis->expire($this->name, $this->seconds);
        }

        return $result === 1;
    }

    /**
     * Release the lock.
     *
     * @return void
     */
    public function release()
    {
        $this->redis->del($this->name);
    }
}
