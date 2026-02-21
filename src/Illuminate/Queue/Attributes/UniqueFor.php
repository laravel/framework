<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class UniqueFor
{
    /**
     * Create a new attribute instance.
     *
     * @param  int  $uniqueFor
     */
    public function __construct(public int $uniqueFor)
    {
        //
    }
}
