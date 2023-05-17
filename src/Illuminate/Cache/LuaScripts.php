<?php

namespace Illuminate\Cache;

use Illuminate\Redis\Lua\LuaScript;

class LuaScripts
{
    /**
     * Get the Lua script to atomically release a lock.
     *
     * KEYS[1] - The name of the lock
     * ARGV[1] - The owner key of the lock instance trying to release it
     *
     * @return \Illuminate\Redis\Lua\LuaScript
     */
    public static function releaseLock()
    {
        return LuaScript::fromPlainScript(<<<'LUA'
if redis.call("get",KEYS[1]) == ARGV[1] then
    return redis.call("del",KEYS[1])
else
    return 0
end
LUA);
    }
}
