<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AfterCommit
{
    /**
     * Create a new attribute instance.
     *
     * @param  bool  $afterCommit
     */
    public function __construct(public bool $afterCommit = true)
    {
        //
    }
}
