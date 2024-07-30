<?php

namespace Illuminate\Container\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Cache implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public ?string $driver = null)
    {
    }

    /**
     * Resolve the cache driver.
     *
     * @param  self  $attribute
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $container->make('cache')->driver($attribute->driver);
    }
}
