<?php

namespace Illuminate\Container\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidatesWhenResolvedTrait;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Validated implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public ?string $key = null, public mixed $default = null)
    {
    }

    /**
     * Resolve the POST data from the request.
     */
    public static function resolve(self $attribute, Container $container): mixed
    {
        return $container->make('request.validated')->validated($attribute->key, $attribute->default);
    }
}
