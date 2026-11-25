<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PerPage
{
    /**
     * Create a new attribute instance.
     *
     * @param  int  $perPage
     */
    public function __construct(public int $perPage)
    {
    }
}
