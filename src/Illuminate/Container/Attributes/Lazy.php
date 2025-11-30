<?php

namespace Illuminate\Container\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;
use ReflectionNamedType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PARAMETER)]
class Lazy implements ContextualAttribute
{
    public static function resolve(self $attribute, Container $container, ReflectionNamedType $type)
    {
        return proxy($type->getName(), static fn () => $container->make($type->getName()));
    }
}
