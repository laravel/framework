<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\SelfBuilding;

abstract class TypedFormRequest implements SelfBuilding
{
    /**
     * Build a new TypedFormRequest instance.
     */
    public static function newInstance(): static
    {
        return Container::getInstance()
            ->make(TypedFormRequestFactory::class, ['requestClass' => static::class])
            ->build();
    }
}
