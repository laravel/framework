<?php

namespace Illuminate\Redis\Lua;

/**
 * The `LuaScriptBuilder` class represents a builder that constructs and executes Redis Lua scripts.
 */
class LuaScriptBuilder
{
    /**
     * The Redis connection instance.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    private $connection;

    /**
     * Constructor method that creates a new LuaScriptBuilder instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $connection  The Redis connection instance.
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * The static constructor method that's create a new instance of LuaScriptBuilder.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $connection  The Redis connection instance.
     * @return self
     */
    public static function create($connection)
    {
        return new self($connection);
    }

    /**
     * Set the Lua script to execute.
     *
     * @param  string  $luaScript  The lua script.
     * @param  bool  $isCacheEnabled  Whether to enable caching feature or not.
     * @return \Illuminate\Redis\Lua\LuaScript
     */
    public function script($luaScript, $isCacheEnabled = false)
    {
        return LuaScript::fromScript($this->connection, $luaScript, $isCacheEnabled);
    }

    /**
     * Set the SHA1 of the Lua script that's already loaded in Redis.
     *
     * @param  string  $sha1  The SHA1 of the Lua script.
     * @return \Illuminate\Redis\Lua\LuaScript
     */
    public function sha1($sha1)
    {
        return LuaScript::fromSha1($this->connection, $sha1);
    }
}
