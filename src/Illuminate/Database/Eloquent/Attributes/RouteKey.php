<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RouteKey
{
    /**
     * Create a new attribute instance.
     */
    public function __construct(public string $field)
    {
    }
}
