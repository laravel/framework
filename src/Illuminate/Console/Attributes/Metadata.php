<?php

namespace Illuminate\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Metadata
{
    /**
     * Create a new attribute instance.
     *
     * @param  array<array-key, mixed>  $metadata
     */
    public function __construct(public array $metadata)
    {
        //
    }
}
