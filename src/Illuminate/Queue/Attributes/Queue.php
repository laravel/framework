<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Queue
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $queue
     */
    public function __construct(public string $queue)
    {
        //
    }
}
