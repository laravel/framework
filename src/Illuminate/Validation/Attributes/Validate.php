<?php

namespace Illuminate\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Validate
{
    /**
     * Create a new attribute instance.
     */
    public function __construct()
    {
    }
}
