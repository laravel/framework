<?php

namespace Illuminate\Auth\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Gate
{
    /**
     * Create a new gate attribute instance.
     */
    public function __construct(
        public string $ability,
        public mixed $arguments = [],
    ) {
    }
}