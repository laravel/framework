<?php

namespace Illuminate\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Isolated
{
    /**
     * Create a new attribute instance.
     *
     * @param  int  $exitCode
     */
    public function __construct(public int $exitCode = 0)
    {
        //
    }
}
