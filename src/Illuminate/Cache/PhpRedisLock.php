<?php

namespace Illuminate\Cache;

use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Lua\LuaScriptArguments;

class PhpRedisLock extends RedisLock
{
    /**
     * Create a new phpredis lock instance.
     *
     * @param  \Illuminate\Redis\Connections\PhpRedisConnection  $redis
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return void
     */
    public function __construct(PhpRedisConnection $redis, string $name, int $seconds, ?string $owner = null)
    {
        parent::__construct($redis, $name, $seconds, $owner);
    }

    /**
     * {@inheritDoc}
     */
    public function release()
    {
        return (bool) $this->redis->lua()
            ->execute(LuaScripts::releaseLock(),LuaScriptArguments::with([$this->name],$this->redis->pack([$this->owner])))
            ->getResult();
    }
}
