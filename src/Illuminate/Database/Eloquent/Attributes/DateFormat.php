<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class DateFormat
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $format
     */
    public function __construct(public string $format)
    {
    }
}
