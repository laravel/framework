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
     * Get the Lua script to delete stale tag members and tags themselves that don't have any members left.
     *
     * KEYS[1] - Connection cache prefix
     * ARGV[1] - Application cache prefix
     * ARGV[2] - Standard reference key const
     * ARGV[3] - Forever reference key const
     *
     * @return string
     */
    public static function flushStaleTags(): string
    {
        return <<<'LUA'
local deadKeys = {}

for _, tagKey in ipairs(redis.call('keys', KEYS[1] .. ARGV[1] .. 'tag:*')) do
    -- So Laravel PHP serializes values before they go into the cache, but not always.
    -- If a value is a numeric (e.g. "6307238514508800660377"), it's written as is,
    -- but all other values (including plain string, e.g. "63072380e1b1c629296883") are serialized.
    -- Unfortunately, this means we have to attempt to support both cases here.
    local tagValue = string.gsub(redis.call('get', tagKey), 's:%d+:"(%w+)";', '%1')

    local referenceKeyPrefix = KEYS[1] .. ARGV[1] .. tagValue .. ':'

    if redis.call('exists', referenceKeyPrefix .. ARGV[2]) > 0 then
        local referenceMembers = redis.call('smembers', referenceKeyPrefix .. ARGV[2])
		local deadMembers = {}

		for _, key in pairs(referenceMembers) do
			if(redis.call('exists', KEYS[1] .. key) == 0) then
				table.insert(deadMembers, key)
			end
		end

		if #deadMembers > 0 then
			redis.call('srem', referenceKeyPrefix .. ARGV[2], unpack(deadMembers))
		end

		if #deadMembers == #referenceMembers then
		    redis.call('del', referenceKeyPrefix .. ARGV[2])
		end
    end

    if redis.call('exists', referenceKeyPrefix .. ARGV[2]) == 0 and redis.call('exists', referenceKeyPrefix .. ARGV[3]) then
        table.insert(deadKeys, tagKey)
    end
end

if(#deadKeys > 0) then
    redis.call('del', unpack(deadKeys))
end
LUA;

    }
}
