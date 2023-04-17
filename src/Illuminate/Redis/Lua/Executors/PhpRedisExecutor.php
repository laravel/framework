<?php

namespace Illuminate\Redis\Lua\Executors;

use Illuminate\Contracts\Redis\LuaScriptExecuteException;
use Illuminate\Contracts\Redis\LuaScriptNoMatchingException;
use Illuminate\Redis\Lua\LuaScriptExecutor;
use RedisException;

/**
 * Lua scripts executor using `PHPRedis` client library for Redis.
 */
class PhpRedisExecutor extends LuaScriptExecutor
{
    /**
     * Returns the last Redis error message or null if there is no error.
     *
     * @return string|null The error message or null
     *
     * @throws \Illuminate\Contracts\Redis\LuaScriptExecuteException If the Redis response indicates a script execution error.
     */
    private function getRedisError()
    {
        try {
            $error = $this->connection->client()->getLastError();
            if ($error !== null) {
                $this->connection->client()->clearLastError();
            }

            return $error;
        } catch (RedisException $e) {
            throw new LuaScriptExecuteException('Failed to retrieve Redis error. '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    protected function handleRedisError($error)
    {
        throw match (explode(' ', $error, 2)[1]) {
            'NOSCRIPT' => new LuaScriptNoMatchingException($error),
            default => new LuaScriptExecuteException($error),
        };
    }

    /**
     * @inheritDoc
     */
    protected function handleRedisResponse($result)
    {
        if ($result !== false) {
            return $result;
        } else {
            if ($error = $this->getRedisError()) {
                $this->handleRedisError($error);
            } else {
                return false;
            }
        }
    }

    /**
     * Load a Lua script into the Redis server.
     *
     * @param  string  $script  The Lua script to load
     * @return string|false Returns the SHA1 hash of the script if successful, or false on failure.
     */
    private function loadScript($script)
    {
        return $this->connection->script('load', $script);
    }

    /**
     * @inheritDoc
     */
    protected function executeWithPlainScript($script, $arguments, $isCachingEnabled)
    {
        if ($isCachingEnabled) {
            try {
                return $this->executeWithHash(sha1($script), $arguments);
            } catch (LuaScriptNoMatchingException) {
                return $this->executeWithHash($this->handleRedisResponse($this->loadScript($script)), $arguments);
            }
        } else {
            return $this->handleRedisResponse(
                $this->connection->eval($script, count($arguments->getKeys()), ...$arguments->toArray())
            );
        }
    }

    /**
     * @inheritDoc
     */
    protected function executeWithHash($sha1, $arguments)
    {
        return $this->handleRedisResponse(
            $this->connection->command('evalsha', [$sha1, $arguments->toArray(), count($arguments->getKeys())])
        );
    }
}
