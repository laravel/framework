<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Routing\Attributes\Authorize;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class MethodMiddlewareTest extends TestCase
{
    public function test_method_middleware_is_respected()
    {
        $route = Route::get('/', MethodMiddlewareTestController::class);

        $this->assertEquals(
            $route->methodMiddleware(),
            ['can:viewAny,' . MethodMiddlewareTestController::class]
        );

        $route = Route::get('/{param}', [MethodMiddlewareTestController::class, 'show']);

        $this->assertEquals(
            $route->methodMiddleware(),
            ['can:view,param']
        );
    }
}

class MethodMiddlewareTestController
{
    #[Authorize('viewAny', MethodMiddlewareTestController::class)]
    public function __invoke()
    {
    }

    #[Authorize('view', 'param')]
    public function show(MethodMiddlewareTestController $param)
    {
    }
}
