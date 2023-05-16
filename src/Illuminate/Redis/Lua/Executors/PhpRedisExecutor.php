<?php

namespace Illuminate\Redis\Lua\Executors;

use Illuminate\Contracts\Redis\LuaScriptExecuteException;
use Illuminate\Redis\Lua\LuaScriptExecutor;
use Illuminate\Redis\Lua\ScriptExecutionResult;

/**
 * Lua script executor using the `PHPRedis` client library for Redis.
 */
class PhpRedisExecutor extends LuaScriptExecutor
{
    /**
     * Prepares the Redis client for script execution by clearing the last error.
     *
     * @return void
     *
     * @throws \RedisException
     */
    private function prepareClient()
    {
        $this->connection->client()->clearLastError();
    }

    /**
     * Retrieves the last Redis error message or null if there is no error.
     *
     * @return \Illuminate\Contracts\Redis\LuaScriptExecuteException|null The error exception or null.
     *
     * @throws \RedisException
     */
    private function getRedisError()
    {
        $error = $this->connection->client()->getLastError();

        if ($error !== null) {
            $this->connection->client()->clearLastError();
            return new LuaScriptExecuteException($error);
        }

        return null;
    }

    /**
     * Handles the response from a script execution.
     *
     * @param  mixed  $result  The result of the script execution.
     * @return \Illuminate\Redis\Lua\ScriptExecutionResult The result of Redis script execution.
     *
     * @throws \RedisException
     */
    protected function handleResponse($result)
    {
        if ($result !== false) {
            return ScriptExecutionResult::success($result);
        }

        $error = $this->getRedisError();
        if ($error !== null) {
            return ScriptExecutionResult::error($error);
        }

        return ScriptExecutionResult::success(false);
    }

    /**
     * @inheritDoc
     */
    protected function executeWithPlainScript($script, $arguments, $isCachingEnabled)
    {
        if ($isCachingEnabled) {
            $executionResult = $this->executeWithHash(sha1($script), $arguments);
            if ($executionResult->isError() && $executionResult->isNoScriptError()) {
                return $this->executeWithHash($this->loadScript($script), $arguments);
            }

            return $executionResult;
        } else {
            $this->prepareClient();

            return $this->handleResponse(
                $this->connection->eval($script, $arguments->getNumberOfKeys(), ...$arguments->toArray())
            );
        }
    }

    /**
     * @inheritDoc
     */
    protected function executeWithHash($sha1, $arguments)
    {
        $this->prepareClient();

        return $this->handleResponse(
            $this->connection->command('evalsha', [$sha1, $arguments->toArray(), count($arguments->getKeys())])
        );
    }
}
