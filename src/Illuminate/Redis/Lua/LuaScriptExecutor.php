<?php

namespace Illuminate\Redis\Lua;

use Illuminate\Redis\Lua\Executors\PhpRedisExecutor;
use Illuminate\Redis\Lua\Executors\PredisExecutor;
use Predis\Client as PredisClient;

/**
 * Abstract class that provides common functionality for executing Redis Lua scripts.
 */
abstract class LuaScriptExecutor
{
    /**
     * The Redis connection instance.
     *
     * @var \Illuminate\Redis\Connections\Connection;
     */
    protected $connection;

    /**
     * Create a new instance with given connection.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $connection  The Redis connection instance.
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get executor based on client.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $connection  The Redis connection instance.
     *
     * @return \Illuminate\Redis\Lua\LuaScriptExecutor The executor instance.
     */
    private static function getExecutor($connection)
    {
        return $connection->client() instanceof PredisClient ? new PredisExecutor($connection) : new PhpRedisExecutor($connection);
    }

    /**
     * Create new instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $connection  The Redis connection instance.
     *
     * @return \Illuminate\Redis\Lua\LuaScriptExecutor The executor instance.
     */
    public static function create($connection)
    {
        return self::getExecutor($connection);
    }

    /**
     * Throw an exception based on the Redis error message.
     *
     * @param  string|\Predis\Response\ServerException|\Predis\Response\Error  $error  The Redis error.
     *
     * @return never
     *
     * @throws \Illuminate\Contracts\Redis\LuaScriptExecuteException If the Redis response indicates a script execution error.
     * @throws \Illuminate\Contracts\Redis\LuaScriptNoMatchingException If the Redis response indicates that the specified script was not found.
     */
    protected abstract function handleRedisError($error);

    /**
     * Handle the result of Redis script execution and throw exception if needed.
     *
     * @param  mixed  $result  The result of Redis script execution.
     *
     * @return mixed The result of Redis script execution.
     *
     * @throws \Illuminate\Contracts\Redis\LuaScriptExecuteException If the Redis response indicates a script execution error.
     * @throws \Illuminate\Contracts\Redis\LuaScriptNoMatchingException If the Redis response indicates that the specified script was not found.
     */
    protected abstract function handleRedisResponse($result);

    /**
     * Execute a Redis Lua script as plain text.
     *
     * @param  string  $script  The Lua script to execute.
     * @param  \Illuminate\Redis\Lua\LuaScriptArguments  $arguments  The arguments to pass to the script.
     * @param  bool  $isCachingEnabled  Indicates whether the script can be cached.
     *
     * @return mixed The result of Redis script execution.
     *
     * @throws \Illuminate\Contracts\Redis\LuaScriptExecuteException If the Redis response indicates a script execution error.
     */
    protected abstract function executeWithPlainScript($script, $arguments, $isCachingEnabled);

    /**
     * Execute a Redis Lua script identified by its SHA-1 hash.
     *
     * @param  string  $sha1  The SHA-1 hash of the Lua script to execute.
     * @param  \Illuminate\Redis\Lua\LuaScriptArguments  $arguments  The arguments to pass to the script.
     *
     * @return mixed The result of Redis script execution.
     *
     * @throws \Illuminate\Contracts\Redis\LuaScriptNoMatchingException If the Redis response indicates that the specified script was not found.
     */
    protected abstract function executeWithHash($sha1, $arguments);

    /**
     * Executes a Redis Lua script with the given arguments.
     *
     * @param  \Illuminate\Redis\Lua\LuaScript  $script  The Lua script to execute.
     * @param  \Illuminate\Redis\Lua\LuaScriptArguments  $arguments  The arguments to pass to the script.
     * @param  bool  $isCachingEnabled  Whether to enable caching for the script. Defaults to false.
     *
     * @return mixed The result of Redis script execution.
     *
     * @throws \Illuminate\Contracts\Redis\LuaScriptExecuteException If the Redis response indicates a script execution error.
     * @throws \Illuminate\Contracts\Redis\LuaScriptNoMatchingException If the Redis response indicates that the specified script was not found.
     */
    public function execute($script, $arguments, $isCachingEnabled = false)
    {
        if ($script->isPlainScript()) {
            return $this->executeWithPlainScript($script->getScript(), $arguments, $isCachingEnabled);
        } else {
            return $this->executeWithHash($script->getSha1(), $arguments);
        }
    }
}
