<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Timestamps
{
    /**
     * Create a new attribute instance.
     *
     * @param  bool  $enabled
     */
    public function __construct(public bool $enabled = true)
    {
    }
}
