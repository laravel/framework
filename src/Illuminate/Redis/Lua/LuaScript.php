<?php

namespace Illuminate\Redis\Lua;

use Illuminate\Redis\Lua\Exception\LuaScriptLoadException;

/**
 * The `LuaScript` class represents a Redis Lua script that can be executed by a Redis connection.
 */
class LuaScript
{
    /**
     * The Redis connection instance.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    public $connection;

    /**
     * The SHA1 hash of the script that's already loaded in Redis.
     *
     * @var string|null
     */
    private $sha1;

    /**
     * The lua script code.
     *
     * @var string|null
     */
    private $script;

    /**
     * The list of the keys to be passed to the script.
     *
     * @var array
     */
    private $keys;

    /**
     * The list of the arguments to be passed to the script.
     *
     * @var array|null
     */
    private $arguments;

    /**
     * The boolean flag that indicates whether the script should be cached or not.
     *
     * @var bool
     */
    private $isCachingEnable;

    /**
     * Create a new LuaScript instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $connection  The Redis connection instance.
     * @param  string|null  $sha1  The SHA1 hash of the script, if it has already been registered on the Redis server.
     * @param  string|null  $script  The lua script code.
     * @param  bool  $isCachingEnable  Whether to enable caching feature or not.
     */
    protected function __construct($connection, $sha1, $script, $isCachingEnable = false)
    {
        $this->connection = $connection;
        $this->sha1 = $sha1;
        $this->script = $script;
        $this->isCachingEnable = $isCachingEnable;
    }

    /**
     * Create a new LuaScript instance from a script string.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $connection  The Redis connection instance.
     * @param  string  $script  The script code.
     * @param  bool  $isCachingEnable  Whether to enable caching feature or not.
     * @return self
     */
    public static function fromScript($connection, $script, $isCachingEnable = false)
    {
        return new static($connection, null, $script, $isCachingEnable);
    }

    /**
     * Create a new LuaScript instance from a SHA1 hash.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $connection  The Redis connection instance.
     * @param  string  $sha1  The SHA1 hash of the script, if it has already been registered on the Redis server.
     * @return self
     */
    public static function fromSha1($connection, $sha1)
    {
        return new static($connection, $sha1, null, false);
    }

    /**
     * The boolean flag that indicates whether the script should be cached or not.
     *
     * @return bool
     */
    public function isCachingEnable()
    {
        return $this->isCachingEnable;
    }

    /**
     * Get the list of the keys to be passed to the script.
     *
     * @return array
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Get the list of the arguments to be passed to the script.
     *
     * @return array|null
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Get the lua script code.
     *
     * @return string|null
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * Get the SHA1 hash of the script that's already loaded in Redis.
     *
     * @return string|null
     */
    public function getSha1()
    {
        return $this->sha1;
    }

    /**
     * Set the list of keys and arguments to be passed to the script.
     *
     * @param  array  $keys  The list of keys to be passed to the script.
     * @param  array|null  $arguments  The list of arguments to be passed to the script.
     * @return $this
     */
    public function with($keys, $arguments = null)
    {
        $this->keys = $keys;
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * Execute the Lua script with the given SHA1 hash using the given Redis connection.
     *
     * @param  string  $sha  The SHA1 hash of the Lua script.
     * @return mixed The result of the script execution.
     */
    protected function executeWithHash($sha)
    {
        return $this->connection->command('evalsh', [$sha, $this->keys, $this->arguments, count($this->keys)]);
    }

    /**
     * Execute the Lua script using EVAL command with the given Redis connection.
     *
     * @return mixed The result of the script execution.
     */
    protected function executeWithScript()
    {
        return $this->connection->eval($this->script, count($this->keys), $this->keys, $this->arguments);
    }

    /**
     * Execute the Lua script using the given Redis connection.
     *
     * @return mixed The result of the script execution.
     *
     * @throws \Illuminate\Redis\Lua\Exception\LuaScriptLoadException If the lua script could not be loaded.
     */
    public function execute()
    {
        if ($this->sha1 !== null) {
            return $this->executeWithHash($this->sha1);
        } else {
            if ($this->isCachingEnable) {
                // Try to execute the script.
                $hash = sha1($this->script);
                $result = $this->executeWithHash($hash);

                if ($result !== false) {
                    return $result;
                }

                // Maybe script not exist in redis, Try to load that
                if ($this->loadScript() === false) {
                    throw new LuaScriptLoadException;
                }

                return $this->executeWithHash($hash);
            } else {
                return $this->executeWithScript();
            }
        }
    }

    /**
     * Load this lua script to redis.
     *
     * @return false|string Returns the SHA1 hash of the loaded script on success, or false on failure.
     */
    public function loadScript()
    {
        return $this->connection->script('load', $this->script);
    }

    /**
     * Check is provided SHA1 key is exists in Redis server.
     *
     * @return bool A boolean indicating whether the provided SHA1 key exists in the Redis server.
     */
    public function exist()
    {
        if ($this->sha1 === null) {
            return true;
        }

        return (bool) $this->connection->script('exists', $this->sha1);
    }
}
