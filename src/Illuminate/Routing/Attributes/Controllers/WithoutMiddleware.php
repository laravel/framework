<?php

namespace Illuminate\Routing\Attributes\Controllers;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class WithoutMiddleware
{
    /**
     * @param  array<string>|null  $only
     * @param  array<string>|null  $except
     */
    public function __construct(
        public string|array $middleware,
        public ?array $only = null,
        public ?array $except = null,
    ) {
    }
}
