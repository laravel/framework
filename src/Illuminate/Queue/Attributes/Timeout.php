<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Timeout
{
    /**
     * Create a new attribute instance.
     *
     * @param  int  $timeout
     */
    public function __construct(public int $timeout)
    {
        //
    }
}
