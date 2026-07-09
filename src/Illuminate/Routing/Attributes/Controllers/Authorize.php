<?php

namespace Illuminate\Routing\Attributes\Controllers;

use Attribute;
use Illuminate\Auth\Middleware\Authorize as AuthorizeMiddleware;
use Illuminate\Support\Arr;
use UnitEnum;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Authorize extends Middleware
{
    /**
     * @param  array<string>|string|null  $models
     */
    public function __construct(
        UnitEnum|string $ability,
        array|string|null $models = null,
        ?array $only = null,
        ?array $except = null,
    ) {
        $middleware = AuthorizeMiddleware::using($ability, ...Arr::wrap($models));

        parent::__construct($middleware, $only, $except);
    }
}
