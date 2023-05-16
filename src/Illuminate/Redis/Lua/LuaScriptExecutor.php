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
     * @return \Illuminate\Redis\Lua\LuaScriptExecutor The executor instance.
     */
    public static function create($connection)
    {
        return self::getExecutor($connection);
    }

    /**
     * Load a Lua script into the Redis server.
     *
     * @param  string  $script  The Lua script to load
     * @return string Returns the SHA1 hash of the script if successful, or throw on failure.
     */
    protected function loadScript($script)
    {
        return $this->handleResponse($this->connection->script('load', $script))->getResult();
    }

    /**
     * Handles the response from a script execution.
     *
     * @param  mixed  $result  The result of the script execution.
     * @return \Illuminate\Redis\Lua\ScriptExecutionResult The result of Redis script execution.
     */
    abstract protected function handleResponse($result);

    /**
     * Execute a Redis Lua script as plain text.
     *
     * @param  string  $script  The Lua script to execute.
     * @param  \Illuminate\Redis\Lua\LuaScriptArguments  $arguments  The arguments to pass to the script.
     * @param  bool  $isCachingEnabled  Indicates whether the script can be cached.
     * @return \Illuminate\Redis\Lua\ScriptExecutionResult The result of Redis script execution.
     */
    abstract protected function executeWithPlainScript($script, $arguments, $isCachingEnabled);

    /**
     * Execute a Redis Lua script identified by its SHA-1 hash.
     *
     * @param  string  $sha1  The SHA-1 hash of the Lua script to execute.
     * @param  \Illuminate\Redis\Lua\LuaScriptArguments  $arguments  The arguments to pass to the script.
     * @return \Illuminate\Redis\Lua\ScriptExecutionResult The result of Redis script execution.
     */
    abstract protected function executeWithHash($sha1, $arguments);

    /**
     * Executes a Redis Lua script with the given arguments.
     *
     * @param  \Illuminate\Redis\Lua\LuaScript  $script  The Lua script to execute.
     * @param  \Illuminate\Redis\Lua\LuaScriptArguments  $arguments  The arguments to pass to the script.
     * @param  bool  $isCachingEnabled  Whether to enable caching for the script. Defaults to false.
     * @return \Illuminate\Redis\Lua\ScriptExecutionResult The result of Redis script execution.
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
