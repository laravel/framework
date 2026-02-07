<?php

namespace Illuminate\Foundation\Http\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Rules
{
    /**
     * @param  array|Closure|string  $rules
     */
    public function __construct(
        public $rules
    ) {

    }
}
