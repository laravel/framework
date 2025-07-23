<?php

namespace Illuminate\Container\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Bind
{
    /**
     * @param  class-string  $concrete
     */
    public function __construct(
        public string $concrete,
    ) {
    }
}
