<?php

namespace Illuminate\Console\Reflections;

use Illuminate\Console\Attributes\Argument;

class ArgumentReflection extends InputReflection
{
    public static function isArgument(\ReflectionProperty $property): bool
    {
        return ! empty($property->getAttributes(Argument::class));
    }
}
