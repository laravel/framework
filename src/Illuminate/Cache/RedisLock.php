<?php

namespace Illuminate\Cache;

class RedisLock extends Lock
{
    const LOCK_SUCCESS = 'OK';
    const IF_NOT_EXIST = 'NX';
    const MILLISECONDS_EXPIRE_TIME = 'PX';
    
    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    protected $redis;

    /**
     * Create a new lock instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $redis
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return void
     */
    public function __construct($redis, $name, $seconds, $owner = null)
    {
        parent::__construct($name, $seconds, $owner);

        $this->redis = $redis;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return bool
     */
    public function acquire()
    {
        $result = $this->redis->set($this->name, 1, self::MILLISECONDS_EXPIRE_TIME, $this->seconds, self::IF_NOT_EXIST);

        return self::LOCK_SUCCESS === (string)$result;
    }

    /**
     * Release the lock.
     *
     * @return void
     */
    public function release()
    {
        $this->redis->eval(LuaScripts::releaseLock(), 1, $this->name, $this->owner);
    }

    /**
     * Releases this lock in disregard of ownership.
     *
     * @return void
     */
    public function forceRelease()
    {
        $this->redis->del($this->name);
    }

    /**
     * Returns the owner value written into the driver for this lock.
     *
     * @return string
     */
    protected function getCurrentOwner()
    {
        return $this->redis->get($this->name);
    }
}
