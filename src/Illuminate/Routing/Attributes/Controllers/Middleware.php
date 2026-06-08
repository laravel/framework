<?php

namespace Illuminate\Routing\Attributes\Controllers;

use Attribute;
use Closure;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware
{
    /**
     * @param  array<string>|null  $only
     * @param  array<string>|null  $except
     */
    public function __construct(
        public Closure|string $middleware,
        public ?array $only = null,
        public ?array $except = null,
    ) {
    }
}
