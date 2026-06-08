<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    /**
     * Create a new attribute instance.
     *
     * @param  string|null  $name
     * @param  string|null  $key
     * @param  string|null  $keyType
     * @param  bool|null  $incrementing
     * @param  bool|null  $timestamps
     * @param  string|null  $dateFormat
     */
    public function __construct(
        public ?string $name = null,
        public ?string $key = null,
        public ?string $keyType = null,
        public ?bool $incrementing = null,
        public ?bool $timestamps = null,
        public ?string $dateFormat = null,
    ) {
    }
}
