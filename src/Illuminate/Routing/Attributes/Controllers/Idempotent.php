<?php

namespace Illuminate\Routing\Attributes\Controllers;

use Attribute;
use Illuminate\Routing\Middleware\Idempotent as IdempotentMiddleware;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Idempotent extends Middleware
{
    public function __construct(
        int $ttl = 86400,
        bool $required = true,
        string $scope = 'user',
        string $header = 'Idempotency-Key',
        ?array $only = null,
        ?array $except = null,
    ) {
        $middleware = IdempotentMiddleware::using(
            ttl: $ttl,
            required: $required,
            scope: $scope,
            header: $header,
        );

        parent::__construct($middleware, $only, $except);
    }
}
