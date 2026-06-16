<?php

namespace Illuminate\Queue\Attributes;

use Attribute;
use UnitEnum;

use function Illuminate\Support\enum_value;

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
        $this->queue = enum_value($queue);
    }
}
