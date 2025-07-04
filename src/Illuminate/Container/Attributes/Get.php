<?php

namespace Illuminate\Container\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Get implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public string $key, public mixed $default = null)
    {
    }

    /**
     * Resolve the GET data from the request.
     */
    public static function resolve(self $attribute, Container $container): mixed
    {
        return $container->make('request')->get($attribute->key, $attribute->default);
    }
}
