<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Observe
{
    /**
     * Create a new attribute instance.
     *
     * @param  array|string  $classes
     * @return void
     */
    public function __construct(array|string $classes)
    {
    }
}
