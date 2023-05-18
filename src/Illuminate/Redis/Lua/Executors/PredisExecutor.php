<?php

namespace Illuminate\Redis\Lua\Executors;

use Illuminate\Redis\Lua\LuaScriptExecutor;
use Illuminate\Redis\Lua\ScriptExecutionResult;
use Predis\Response\Error as PredisError;
use Predis\Response\ServerException;

/**
 * Lua script executor using the `Predis` client library for Redis.
 */
class PredisExecutor extends LuaScriptExecutor
{
    /**
     * @inheritDoc
     */
    protected function handleResponse($result)
    {
        if ($result instanceof PredisError) {
            return ScriptExecutionResult::error($result);
        }

        return ScriptExecutionResult::success($result);
    }

    /**
     * Executes the given callback function with a try-catch block and handles any exceptions thrown.
     *
     * @param  callable  $callback  The callback function to execute.
     * @return ScriptExecutionResult The result of handling the response.
     */
    private function executeWithTryCatch($callback)
    {
        try {
            return $this->handleResponse($callback());
        } catch (ServerException $e) {
            return ScriptExecutionResult::error($e);
        }
    }

    /**
     * @inheritDoc
     */
    protected function executeWithPlainScript($script, $arguments, $isCachingEnabled)
    {
        if ($isCachingEnabled) {
            $result = $this->executeWithHash(sha1($script), $arguments);

            if ($result->isError() && $result->isNoScriptError()) {
                return $this->executeWithHash($this->loadScript($script), $arguments);
            } else {
                return $result;
            }
        } else {
            return $this->executeWithTryCatch(fn () => $this->connection->eval($script, $arguments->getNumberOfKeys(), ...$arguments->toArray()));
        }
    }

    /**
     * @inheritDoc
     */
    protected function executeWithHash($sha1, $arguments)
    {
        return $this->executeWithTryCatch(fn () => $this->connection->command('evalsha', [$sha1, count($arguments->getKeys()), ...$arguments->toArray()]));
    }
}
