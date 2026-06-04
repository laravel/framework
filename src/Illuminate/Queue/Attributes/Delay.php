<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Delay
{
    /**
     * Create a new attribute instance.
     *
     * @param  int  $delay
     */
    public function __construct(public int $delay)
    {
        //
    }
}
