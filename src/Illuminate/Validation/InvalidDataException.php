<?php

namespace Illuminate\Validation;

use Exception;

class InvalidDataException extends Exception
{
    public static function asteriskUsage()
    {
        return new static('Asterisks are not allowed as keys in validation data.');
    }
}
