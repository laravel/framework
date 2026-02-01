<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class KeyType
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $type
     */
    public function __construct(public string $type)
    {
    }
}
