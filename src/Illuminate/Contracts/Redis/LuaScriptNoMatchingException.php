<?php

namespace Illuminate\Contracts\Redis;

use Exception;

/**
 * The exception is thrown when no Lua script matches the given sha1 hash in the Redis server.
 */
class LuaScriptNoMatchingException extends Exception
{
    //
}
