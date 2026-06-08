<?php

namespace Illuminate\Foundation\Http\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RedirectTo
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $url
     */
    public function __construct(public string $url)
    {
        //
    }
}
