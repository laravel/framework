<?php

namespace Illuminate\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Aliases
{
    /**
     * Create a new attribute instance.
     *
     * @param  array<int, string>  $aliases
     */
    public function __construct(public array $aliases)
    {
        //
    }
}
