<?php

namespace Illuminate\Routing\Attributes;

use Attribute;

#[\Attribute(Attribute::TARGET_METHOD)]
class WithoutMiddleware
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string|array<class-string>  $middleware
     */
    public function __construct(public string|array $middleware)
    {
    }
}
