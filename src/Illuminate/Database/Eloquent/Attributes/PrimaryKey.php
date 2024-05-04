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
     * @param  string  $type
     * @param  bool  $incrementing
     * @return void
     */
    public function __construct(
        public string $name = 'id',
        public string $type = 'int',
        public bool $incrementing = true,
    ) {
    }
}
