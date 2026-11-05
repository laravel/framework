<?php

namespace Illuminate\Foundation\Testing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class WithoutMigrationEvents
{
    /**
     * Create a new attribute instance.
     */
    public function __construct()
    {
        //
    }
}
