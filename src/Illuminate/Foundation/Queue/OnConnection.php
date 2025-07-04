<?php

namespace Illuminate\Foundation\Queue;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class OnConnection
{
    /**
     * @param  string|\UnitEnum  $connection
     * @return void
     */
    public function __construct(public $connection)
    {
    }
}
