<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PrimaryKey
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $name
     * @param  string|null  $type
     * @param  bool|null  $incrementing
     */
    public function __construct(
        public string $name,
        public ?string $type = null,
        public ?bool $incrementing = null,
    ) {
    }
}
