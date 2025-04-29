<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class WithoutRelations
{
    public function __construct(public array $relations)
    {
        //
    }
}
