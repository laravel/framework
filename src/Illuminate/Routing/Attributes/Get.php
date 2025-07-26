<?php

namespace Illuminate\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Get extends RouteAttribute
{
    /**
     * @param  string  $path
     * @param  string|null  $name
     * @param  array|string  $middleware
     * @param  array  $where
     */
    public function __construct(
        $path,
        $name = null,
        $middleware = [],
        $where = []
    ) {
        parent::__construct($path, ['GET', 'HEAD'], $name, $middleware, $where);
    }
}
