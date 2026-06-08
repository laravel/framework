<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_CLASS)]
class Connection
{
    /**
     * Create a new attribute instance.
     *
     * @param  UnitEnum|string  $name
     */
    public function __construct(public UnitEnum|string $name)
    {
    }
}
