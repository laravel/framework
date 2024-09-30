<?php

namespace Illuminate\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsOptimize
{

    /**
     * Create a new class instance.
     *
     * @param string $name
     * @param bool $clear
     */
    public function __construct(public string $name, public bool $clear = false)
    {
    }
    
}
