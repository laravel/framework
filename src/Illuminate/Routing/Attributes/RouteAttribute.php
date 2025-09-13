<?php

namespace Illuminate\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RouteAttribute
{
    /**
     * @param  string  $path
     * @param  array  $methods
     * @param  string|null  $name
     * @param  array|string  $middleware
     * @param  array  $where
     */
    public function __construct(
        public string $path,
        public array $methods,
        public ?string $name = null,
        public array|string $middleware = [],
        public array $where = []
    ) {
    }
}
