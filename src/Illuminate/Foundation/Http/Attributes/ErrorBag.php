<?php

namespace Illuminate\Foundation\Http\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ErrorBag
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
