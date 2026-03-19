<?php

namespace Illuminate\Queue\Attributes;

use Attribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_CLASS)]
class Connection
{
    /**
     * Create a new attribute instance.
     *
     * @param  UnitEnum|string  $connection
     */
    public function __construct(public UnitEnum|string $connection)
    {
        //
    }
}
