<?php

namespace Illuminate\Contracts\Redis;

use Exception;

/**
 * The exception is thrown when there is an error executing a Lua script in Redis.
 */
class LuaScriptExecuteException extends Exception
{
    /**
     * Gets the type of the error returned by Redis.
     *
     * @return string
     */
    public function getErrorType()
    {
        return explode(' ', $this->getMessage(), 2)[0];
    }
}
