<?php

namespace Illuminate\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Matches extends RouteAttribute
{
    /**
     * @param  array  $methods
     * @param  string  $path
     * @param  string|null  $name
     * @param  array|string  $middleware
     * @param  array  $where
     */
    public function __construct(
        $methods,
        $path,
        $name = null,
        $middleware = [],
        $where = []
    ) {
        parent::__construct($path, $methods, $name, $middleware, $where);
    }
}
