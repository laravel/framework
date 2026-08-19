<?php

namespace Illuminate\Tests\App\ValueObjects;

use Illuminate\Tests\App\Casts\ValueObjectCaster;

class ValueObjectWithCasterInstance extends ValueObject
{
    public static function castUsing(array $arguments)
    {
        return new ValueObjectCaster;
    }
}
