<?php

namespace Illuminate\Routing\Exceptions;

use Exception;

class NotImplementedException extends Exception
{
    public static function create()
    {
        return new static();
    }
}
