<?php

namespace Illuminate\Config\Attributes;

use Attribute;

#[Attribute]
class InjectedConfig
{
    public function __construct(public string $key, public mixed $default = null)
    {
    }
}
