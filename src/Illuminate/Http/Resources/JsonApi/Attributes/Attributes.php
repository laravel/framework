<?php

namespace Illuminate\Http\Resources\JsonApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Attributes
{
    public function __construct(public array $attributes = [])
    {
    }
}
