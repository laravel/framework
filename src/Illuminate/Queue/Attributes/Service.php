<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Service
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $name
     */
    public function __construct(public string $name)
    {
        //
    }
}
