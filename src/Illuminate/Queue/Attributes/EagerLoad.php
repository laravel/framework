<?php

namespace Illuminate\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class EagerLoad
{
    /**
     * @param  list<string>  $relations
     */
    public function __construct(public array $relations)
    {
        //
    }
}
