<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Scope
{
    /**
     * Create a new attribute instance.
     *
     * @param  array|string  $classes
     * @return void
     */
    public function __construct()
    {
    }
}
