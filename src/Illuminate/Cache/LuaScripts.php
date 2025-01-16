<?php

namespace Illuminate\Cache;

class LuaScripts
{
    /**
     * Get the Lua script to atomically release a lock.
     *
     * KEYS[1] - The name of the lock
     * ARGV[1] - The owner key of the lock instance trying to release it
     *
     * @return string
     */
    public static function releaseLock()
    {
        return <<<'LUA'
if redis.call("get",KEYS[1]) == ARGV[1] then
    return redis.call("del",KEYS[1])
else
    return 0
end
LUA;
    }

    /**
     * Get the Lua script that sets a key only when it does not yet exist.
     *
     * KEYS[1] - The name of the key
     * ARGV[1] - Value of the key
     * ARGV[2] - Time in seconds how long to keep the key
     *
     * @return string
     */
    public static function add()
    {
        return <<<'LUA'
return redis.call('exists',KEYS[1])<1 and redis.call('setex',KEYS[1],ARGV[2],ARGV[1])
LUA;
    }
}
