<?php

namespace Illuminate\Queue\Attributes;

use Attribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_CLASS)]
class Queue
{
    /**
     * Create a new attribute instance.
     *
     * @param  UnitEnum|string  $queue
     */
    public function __construct(public UnitEnum|string $queue)
    {
        //
    }
}
