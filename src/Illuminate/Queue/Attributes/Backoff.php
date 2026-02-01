<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Backoff
{
    /**
     * Create a new attribute instance.
     *
     * @param  array<int>  $backoff
     */
    public function __construct(public array $backoff)
    {
        //
    }
}
