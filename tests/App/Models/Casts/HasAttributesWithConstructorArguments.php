<?php

namespace Illuminate\Tests\App\Models\Casts;

class HasAttributesWithConstructorArguments extends HasAttributesWithoutConstructor
{
    public function __construct($someValue)
    {
    }
}
