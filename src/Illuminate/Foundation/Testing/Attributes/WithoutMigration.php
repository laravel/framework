<?php

namespace Illuminate\Foundation\Testing\Attributes;

use Attribute;
use Illuminate\Support\Arr;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class WithoutMigration
{
    /**
     * @var non-empty-list<string>
     */
    public array $migrations;

    /**
     * @param  string|non-empty-list<string>  $migration
     */
    public function __construct(string|array $migration)
    {
        $this->migrations = Arr::wrap($migration);
    }
}
