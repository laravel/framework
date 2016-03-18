<?php

namespace Illuminate\Bus;

use RuntimeException;
use ReflectionParameter;

class MarshalException extends RuntimeException
{
    /**
     * Throw new a new exception.
     *
     * @param  string  $command
     * @param  \ReflectionParameter  $parameter
     * @return void
     */
    public static function whileMapping($command, ReflectionParameter $parameter)
    {
        throw new static("Unable to map parameter [{$parameter->name}] to command [{$command}]");
    }
}
