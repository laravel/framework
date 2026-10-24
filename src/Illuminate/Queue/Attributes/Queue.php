<?php

namespace Illuminate\Queue\Attributes;

use Attribute;
use Closure;
use UnitEnum;

use function Illuminate\Support\enum_value;

#[Attribute(Attribute::TARGET_CLASS)]
class Queue
{
    /**
     * Create a new attribute instance.
     *
     * @param  \UnitEnum|string|\Closure(\Illuminate\Contracts\Container\Container): (\UnitEnum|string)  $queue
     */
    public function __construct(public UnitEnum|string|Closure $queue)
    {
        $this->queue = $queue instanceof Closure ? $queue : enum_value($queue);
    }
}
