<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class WithoutTimestamps
{
    /**
     * Create a new attribute instance.
     */
    public function __construct()
    {
        //
    }
}
