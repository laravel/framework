<?php

namespace Illuminate\Foundation\Http\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapFrom
{
    /**
     * @param  string  $name  The request field to map from.
     */
    public function __construct(public $name)
    {
    }
}
