<?php

namespace Illuminate\View\Exception;

use InvalidArgumentException;

class ViewParamException extends InvalidArgumentException
{
    /**
     * Creates a new exception.
     *
     * @param  string  $file
     * @param  string  $variableName
     * @param  string  $expectedType
     * @param  string  $actualType
     * @return static
     */
    public static function forVariable($file, $variableName, $expectedType, $actualType)
    {
        return new static("Invalid parameter \${$variableName} supplied to view {$file}: expecting type of {$expectedType} while {$actualType} given");
    }
}
