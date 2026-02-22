<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Connection
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $connection
     */
    public function __construct(public string $connection)
    {
        //
    }
}
