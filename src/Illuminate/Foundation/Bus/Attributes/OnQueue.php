<?php

namespace Illuminate\Foundation\Bus\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class OnQueue
{
    /**
     * @param  string|\UnitEnum  $queue
     * @return void
     */
    public function __construct(public $queue)
    {
    }
}
