<?php

namespace Illuminate\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class BindRoute
{
    /**
     * Create a new attribute instance.
     */
    public function __construct(
        public ?string $parameter = null,
        public ?string $field = null,
        public ?bool $withTrashed = null,
    ) {
    }
}
