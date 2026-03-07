<?php

namespace Illuminate\Http\Resources\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Collects
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $class
     */
    public function __construct(public string $class)
    {
        //
    }
}
