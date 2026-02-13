<?php

namespace Illuminate\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Signature
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $signature
     */
    public function __construct(public string $signature)
    {
        //
    }
}
