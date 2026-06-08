<?php

namespace Illuminate\Http\Resources\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PreserveKeys
{
    /**
     * Create a new attribute instance.
     */
    public function __construct()
    {
        //
    }
}
