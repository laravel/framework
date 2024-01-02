<?php

namespace Illuminate\Database;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Transactional
{
    public function __construct(
        public ?string $connection = null,
    )
    {
        //
    }
}
