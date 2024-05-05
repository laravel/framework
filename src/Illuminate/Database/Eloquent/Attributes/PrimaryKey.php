<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PrimaryKey
{
    /**
     * Create a new attribute instance.
     *
     * @param  string|null  $name
     * @param  string|null  $type
     * @param  bool|null  $incrementing
     * @return void
     */
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public ?bool $incrementing = null,
    ) {
    }
}
