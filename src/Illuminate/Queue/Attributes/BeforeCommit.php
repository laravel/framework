<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class BeforeCommit
{
    /**
     * Create a new attribute instance.
     *
     * @param  bool  $beforeCommit
     */
    public function __construct(public bool $beforeCommit = true)
    {
        //
    }
}
