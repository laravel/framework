<?php

namespace Illuminate\Http\Resources\JsonApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Wraps
{
    public function __construct(public string $wrapper = 'data')
    {
    }
}
