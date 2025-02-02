<?php

namespace Illuminate\Foundation\Testing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class WithoutMigration
{
    public function __construct(public string $migration)
    {
        //
    }
}
