<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Timeout
{
    /**
     * Create a new attribute instance.
     *
     * @param  int  $timeout  Seconds before the job is considered timed out.
     */
    public function __construct(public int $timeout)
    {
        //
    }
}
