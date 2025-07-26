<?php

namespace Illuminate\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Group
{
    /**
     * @param  string|null  $prefix
     * @param  string|null  $name
     * @param  array|string  $middleware
     * @param  array  $where
     */
    public function __construct(
        public ?string $prefix = null,
        public ?string $name = null,
        public array|string $middleware = [],
        public array $where = []
    ) {
    }
}
