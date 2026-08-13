<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Defaults
{
    /**
     * Create a new attribute instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public array $attributes,
    ) {
        //
    }
}
