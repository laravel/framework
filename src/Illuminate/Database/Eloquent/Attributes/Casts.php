<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;
use Closure;

#[Attribute(Attribute::TARGET_CLASS)]
class Casts
{
    /**
     * Create a new attribute instance.
     *
     * @param  array<string, mixed>|(Closure(): array<string, mixed>)  $casts
     */
    public function __construct(public array|Closure $casts)
    {
    }
}
