<?php

namespace Illuminate\Foundation\Http\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapFrom
{
    public function __construct(public string $name)
    {
    }
}
