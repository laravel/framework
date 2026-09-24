<?php

namespace Illuminate\Services\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RemoteName
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $name
     */
    public function __construct(public string $name)
    {
        //
    }
}
