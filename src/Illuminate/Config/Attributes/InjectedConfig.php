<?php

namespace Illuminate\Config\Attributes;

use Attribute;

#[Attribute]
abstract class InjectedConfig
{
    abstract public function __construct(string $key = '');
}
