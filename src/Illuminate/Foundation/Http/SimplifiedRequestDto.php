<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Contracts\Container\SelfBuilding;

class SimplifiedRequestDto implements SelfBuilding
{
    public static function newInstance()
    {
        return (new RequestDtoHandler(static::class))->handle();
    }
}
