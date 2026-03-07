<?php

namespace Illuminate\Database\Eloquent\Factories\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class UseModel
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
