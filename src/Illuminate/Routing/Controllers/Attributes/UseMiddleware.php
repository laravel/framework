<?php

namespace Illuminate\Routing\Controllers\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class UseMiddleware
{
    /**
     * Create a new attribute instance.
     *
     * @param  array|string  $middlewares
     * @return void
     */
    public function __construct(public array|string $middlewares)
    {
    }
}
