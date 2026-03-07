<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class MaxExceptions
{
    /**
     * Create a new attribute instance.
     *
     * @param  int  $maxExceptions
     */
    public function __construct(public int $maxExceptions)
    {
        //
    }
}
