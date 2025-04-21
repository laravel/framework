<?php

namespace Illuminate\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class SeedTask
{
    /**
     * Create a new Seed Task instance.
     */
    public function __construct(public string $as = '')
    {
        //
    }
}
