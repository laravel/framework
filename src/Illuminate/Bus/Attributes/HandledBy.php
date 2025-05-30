<?php

namespace Illuminate\Bus\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class HandledBy
{
    /**
     * Create a new HandledBy instance.
     *
     * @param  string  $handler
     */
    public function __construct(public string $handler)
    {
    }
}
