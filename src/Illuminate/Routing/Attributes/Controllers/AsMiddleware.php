<?php

namespace Illuminate\Routing\Attributes\Controllers;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsMiddleware
{
    /**
     * Create a new attribute instance.
     */
    public function __construct(
        public string $alias
    ) {
    }
}
