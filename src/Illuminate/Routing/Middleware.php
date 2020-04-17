<?php

namespace Illuminate\Routing;

abstract class Middleware
{
    public static function __set_state($data)
    {
        return new static(...array_values($data));
    }
}
