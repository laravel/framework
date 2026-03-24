<?php

namespace Illuminate\Http\Resources\JsonApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Relationships
{
    public function __construct(public array $relationships = [])
    {
    }
}
