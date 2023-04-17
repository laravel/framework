<?php

namespace Illuminate\Redis\Lua\Executors;

use Illuminate\Contracts\Redis\LuaScriptExecuteException;
use Illuminate\Contracts\Redis\LuaScriptNoMatchingException;
use Illuminate\Redis\Lua\LuaScriptExecutor;
use Predis\Response\Error as PredisError;
use Predis\Response\ServerException;

/**
 * Lua scripts executor using `Predis` client library for Redis.
 */
class PredisExecutor extends LuaScriptExecutor
{
    /**
     * @inheritDoc
     */
    protected function handleRedisError($error)
    {
        throw match ($error->getErrorType()) {
            'NOSCRIPT' => new LuaScriptNoMatchingException($error),
            default => new LuaScriptExecuteException($error),
        };
    }

    /**
     * @inheritDoc
     */
    protected function handleRedisResponse($result)
    {
        if ($result instanceof PredisError) {
            $this->handleRedisError($result);
        }

        return $result;
    }

    /**
     * Load the given Lua script into Redis and return its SHA-1 hash.
     *
     * @param  string  $script  The Lua script to load.
     * @return string The SHA-1 hash of the loaded script.
     *
     * @throws \Illuminate\Contracts\Redis\LuaScriptExecuteException If there was an error executing the script in Redis.
     */
    private function loadScript($script)
    {
        try {
            return $this->connection->script('load', $script);
        } catch (ServerException $e) {
            throw new LuaScriptExecuteException($e->getMessage(), 0, $e);
        }
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
                return $this->executeWithHash($this->loadScript($script), $arguments);
            }
        } else {
            try {
                return $this->handleRedisResponse($this->connection->eval($script, $arguments->getNumberOfKeys(), ...$arguments->toArray()));
            } catch (ServerException $e) {
                $this->handleRedisError($e);
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function executeWithHash($sha1, $arguments)
    {
        try {
            return $this->handleRedisResponse($this->connection->evalsha($sha1, $arguments->getNumberOfKeys(), ...$arguments->toArray()));
        } catch (ServerException $e) {
            $this->handleRedisError($e);
        }
    }
}
