<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\SelfBuilding;

class TypedFormRequest implements SelfBuilding
{
    public static function newInstance(): static
    {
        return Container::getInstance()
            ->make(TypedFormRequestBuilder::class, ['requestClass' => static::class])
            ->handle();
    }
}
