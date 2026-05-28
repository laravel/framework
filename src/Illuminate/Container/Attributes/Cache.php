<?php

namespace Illuminate\Container\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Cache implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public UnitEnum|string|null $store = null,
        public bool $memoized = false,
    ) {
    }

    /**
     * Resolve the cache store.
     *
     * @param  self  $attribute
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $attribute->memoized
            ? $container->make('cache')->memo($attribute->store)
            : $container->make('cache')->store($attribute->store);
    }
}
