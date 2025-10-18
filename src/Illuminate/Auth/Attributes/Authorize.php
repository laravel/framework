<?php

namespace Illuminate\Auth\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Authorize
{
    /**
     * Create a new authorize attribute instance.
     */
    public function __construct(
        public string $ability,
        public mixed $arguments = [],
    ) {
    }
}