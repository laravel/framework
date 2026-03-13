<?php

namespace Illuminate\Redis\Limiters;

class SlidingWindowDurationLimiter extends AbstractDurationLimiter
{
    /**
     * Get the Lua script for acquiring a lock using the sliding window algorithm.
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
    redis.call('HMSET', KEYS[1], 'window_start', ARGV[2], 'current', 1, 'previous', 0)
    redis.call('EXPIRE', KEYS[1], ARGV[3] * 2)
    return {1, ARGV[2] + ARGV[3], ARGV[4] - 1}
end

if redis.call('EXISTS', KEYS[1]) == 0 then
    return reset()
end

local window_start = tonumber(redis.call('HGET', KEYS[1], 'window_start'))
local now = tonumber(ARGV[2])
local decay = tonumber(ARGV[3])
local max_locks = tonumber(ARGV[4])

-- If we're past two full windows, reset entirely
if now >= window_start + (decay * 2) then
    return reset()
end

-- If we've moved into a new window, rotate the old current count into
-- previous and start a fresh window. The effective count includes the
-- weighted previous window plus the new hit we're about to record.
if now >= window_start + decay then
    local current = tonumber(redis.call('HGET', KEYS[1], 'current') or 0)
    local elapsed = now - (window_start + decay)
    local overlap = 1 - (elapsed / decay)
    if overlap < 0 then overlap = 0 end
    local effective = math.floor(overlap * current) + 1

    redis.call('HMSET', KEYS[1], 'window_start', now, 'current', 1, 'previous', current)
    redis.call('EXPIRE', KEYS[1], decay * 2)

    if effective <= max_locks then
        return {1, now + decay, max_locks - effective}
    end
    return {0, now + decay, 0}
end

-- We're within the current window
local current = tonumber(redis.call('HGET', KEYS[1], 'current') or 0)
local previous = tonumber(redis.call('HGET', KEYS[1], 'previous') or 0)
local elapsed = now - window_start
local overlap = 1 - (elapsed / decay)
if overlap < 0 then overlap = 0 end

local effective = math.floor(overlap * previous) + current

if effective < max_locks then
    redis.call('HINCRBY', KEYS[1], 'current', 1)
    return {1, window_start + decay, max_locks - effective - 1}
end

return {0, window_start + decay, 0}
LUA;
    }

    /**
     * Get the Lua script to determine if the key has been "accessed" too many times.
     *
     * KEYS[1] - The limiter name
     * ARGV[1] - Current time in microseconds
     * ARGV[2] - Current time in seconds
     * ARGV[3] - Duration of the bucket
     * ARGV[4] - Allowed number of tasks
     *
     * @return string
     */
    protected function tooManyAttemptsLuaScript()
    {
        return <<<'LUA'
if redis.call('EXISTS', KEYS[1]) == 0 then
    return {0, ARGV[2] + ARGV[3]}
end

local window_start = tonumber(redis.call('HGET', KEYS[1], 'window_start'))
local now = tonumber(ARGV[2])
local decay = tonumber(ARGV[3])
local max_locks = tonumber(ARGV[4])

if now >= window_start + (decay * 2) then
    return {0, now + decay}
end

if now >= window_start + decay then
    local current = tonumber(redis.call('HGET', KEYS[1], 'current') or 0)
    local elapsed = now - (window_start + decay)
    local overlap = 1 - (elapsed / decay)
    if overlap < 0 then overlap = 0 end
    local effective = math.floor(overlap * current)
    return {now + decay, math.max(0, max_locks - effective)}
end

local current = tonumber(redis.call('HGET', KEYS[1], 'current') or 0)
local previous = tonumber(redis.call('HGET', KEYS[1], 'previous') or 0)
local elapsed = now - window_start
local overlap = 1 - (elapsed / decay)
if overlap < 0 then overlap = 0 end

local effective = math.floor(overlap * previous) + current
return {window_start + decay, math.max(0, max_locks - effective)}
LUA;
    }
}
