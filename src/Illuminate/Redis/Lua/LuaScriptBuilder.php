<?php

namespace Illuminate\Redis\Lua;

use Illuminate\Redis\Connections\Connection;

/**
 * The `LuaScriptBuilder` class represents a builder that constructs and executes Redis Lua scripts.
 */
class LuaScriptBuilder
{
    /**
     * The Redis connection instance
     *
     * @var Connection
     */
    public readonly Connection $connection;

    /**
     * Constructor method that creates a new LuaScriptBuilder instance.
     *
     * @param  Connection  $connection  The Redis connection instance
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * The static constructor method that's create a new instance of LuaScriptBuilder
     * @param  Connection  $connection  The Redis connection instance
     * @return static
     */
    public static function create(Connection $connection): self
    {
        return new self($connection);
    }

    /**
     * Set the Lua script to execute
     * @param  string  $luaScript  The lua script
     * @param  bool  $isCacheEnabled  Whether to enable caching feature or not.
     * @return LuaScript
     */
    public function script(string $luaScript, bool $isCacheEnabled = false): LuaScript
    {
        return LuaScript::fromScript($this->connection, $luaScript, $isCacheEnabled);
    }

    /**
     * Set the SHA1 of the Lua script that's already loaded in Redis
     *
     * @param  string  $sha1  The SHA1 of the Lua script
     * @return LuaScript
     */
    public function sha1(string $sha1): LuaScript
    {
        return LuaScript::fromSha1($this->connection, $sha1);
    }

}
