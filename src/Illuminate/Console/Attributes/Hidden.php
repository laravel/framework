<?php

namespace Illuminate\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Hidden
{
    /**
     * Create a new Hidden attribute instance.
     */
    public function __construct() {}
}
