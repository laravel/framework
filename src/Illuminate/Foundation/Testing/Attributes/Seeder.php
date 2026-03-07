<?php

namespace Illuminate\Foundation\Testing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Seeder
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
