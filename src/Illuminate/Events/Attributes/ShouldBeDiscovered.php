<?php

namespace Illuminate\Events\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ShouldBeDiscovered
{
    /**
     * Create a new attribute instance.
     *
     * @param  bool  $shouldBeDiscovered
     */
    public function __construct(public bool $shouldBeDiscovered = true)
    {
        //
    }
}
