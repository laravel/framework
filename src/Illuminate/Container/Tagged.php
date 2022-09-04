<?php

namespace Illuminate\Container;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class Tagged
{
    public function __construct(
        public string $tag
    ) {}
}
