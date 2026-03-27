<?php

namespace Illuminate\Routing\Attributes\Controllers;

use Attribute;
use Illuminate\Http\Middleware\SetCacheHeaders as SetCacheHeadersMiddleware;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class SetCacheHeaders extends Middleware
{
    /**
     * @param  array<mixed>|string  $options
     * @param  array<string>|null  $only
     * @param  array<string>|null  $except
     */
    public function __construct(
        public array|string $options,
        public ?array $only = null,
        public ?array $except = null,
    ) {
        parent::__construct(SetCacheHeadersMiddleware::using($options), $only, $except);
    }
}
