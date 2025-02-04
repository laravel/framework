<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RoutedBy
{
    /**
     * Create a new attribute instance.
     *
     * @param  string  $key
     * @return void
     */
    public function __construct(public string $key)
    {
    }
}
