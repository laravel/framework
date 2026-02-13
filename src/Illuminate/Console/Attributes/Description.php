<?php

namespace Illuminate\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Description
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $description
     */
    public function __construct(public string $description)
    {
        //
    }
}
