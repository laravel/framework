<?php

namespace Illuminate\Attributes\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware
{
    /**
     * @param string $name
     * @param string[] $arguments
     * @param array $options
     */
    function __construct(public string $name, public array $arguments = [], public array $options = [])
    {
    }
}
