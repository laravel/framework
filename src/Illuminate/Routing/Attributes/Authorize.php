<?php

namespace Illuminate\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Authorize
{
    public function __construct(
        public string $ability,
        public string $classOrParameterName,
    ) {
    }
}
