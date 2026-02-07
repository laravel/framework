<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\SelfBuilding;

class TypedFormRequest implements SelfBuilding
{
    public static function newInstance()
    {
        return Container::getInstance()
            ->make(RequestDtoHandler::class, ['requestClass' => static::class])
            ->handle();
    }
}
