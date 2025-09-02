<?php

namespace Illuminate\Container\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ShouldQueue extends Database
{
}
