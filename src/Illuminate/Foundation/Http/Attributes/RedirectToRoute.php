<?php

namespace Illuminate\Foundation\Http\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RedirectToRoute
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $route
     */
    public function __construct(public string $route)
    {
        //
    }
}
