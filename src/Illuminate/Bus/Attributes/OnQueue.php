<?php

namespace Illuminate\Bus\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class OnQueue
{
    public function __construct(public readonly string $queue)
    {
    }
}
