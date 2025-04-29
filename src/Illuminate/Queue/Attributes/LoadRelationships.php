<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class LoadRelationships
{
    public function __construct(public array $relations)
    {
        //
    }
}
