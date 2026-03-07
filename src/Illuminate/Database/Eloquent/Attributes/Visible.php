<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Visible
{
    /**
     * Create a new attribute instance.
     *
     * @param  array<int, string>  $columns
     */
    public function __construct(public array $columns)
    {
    }
}
