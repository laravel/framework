<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Tries
{
    /**
     * Create a new attribute instance.
     *
     * @param  int  $tries
     */
    public function __construct(public int $tries)
    {
        //
    }
}
