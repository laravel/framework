<?php

namespace Illuminate\Routing\Attributes\Controllers;

use Attribute;
use Illuminate\Routing\Middleware\ThrottleRequests;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class DisableRateLimiting extends WithoutMiddleware
{
    /**
     * @param  array<string>|null  $only
     * @param  array<string>|null  $except
     */
    public function __construct(
        ?array $only = null,
        ?array $except = null,
    ) {
        parent::__construct(ThrottleRequests::class, $only, $except);
    }
}
