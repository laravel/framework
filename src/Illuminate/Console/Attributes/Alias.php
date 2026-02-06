<?php

namespace Illuminate\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Alias
{
    /**
     * Create a new Alias attribute instance.
     *
     * @param  string  $name  The alias name
     */
    public function __construct(
        public string $name,
    ) {}
}
