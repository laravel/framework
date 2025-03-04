<?php

declare(strict_types=1);

namespace Illuminate\Container\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Alias implements ContextualAttribute
{
    public function __construct(
        public string $alias,
    ) {
    }

    /**
     * Resolve the tag.
     *
     * @param  self  $attribute
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return mixed
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $container->get($attribute->alias);
    }
}
