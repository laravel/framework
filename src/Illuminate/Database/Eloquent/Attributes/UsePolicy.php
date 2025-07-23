<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class UsePolicy
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string<*>  $class
     */
    public function __construct(public string $class)
    {
    }
}
