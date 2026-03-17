<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Cast
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $attribute  The model attribute name to cast.
     * @param  string  $as  The cast type or class-string of a custom cast.
     */
    public function __construct(
        public string $attribute,
        public string $as,
    ) {
    }
}
