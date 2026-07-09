<?php

namespace Illuminate\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Help
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $help
     */
    public function __construct(public string $help)
    {
        //
    }
}
