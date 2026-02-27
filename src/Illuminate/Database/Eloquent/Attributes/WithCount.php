<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class WithCount
{
    /**
     * Create a new attribute instance.
     *
     * @param  array<int, string>  $relations
     */
    public function __construct(public array $relations)
    {
    }
}
