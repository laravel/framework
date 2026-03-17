<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Routing\Attributes\Controllers\Throttle;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class ThrottleMiddlewareAttributeTest extends TestCase
{
    public function test_named_limiter_attribute_is_resolved(): void
    {
        $route = Route::get('/', [ThrottleAttributeController::class, 'index']);

        $this->assertEquals([
            ThrottleRequests::using('api'),
            ThrottleRequests::using('uploads'),
        ], $route->controllerMiddleware());
    }

    public function test_method_scoped_attribute_is_respected(): void
    {
        $route = Route::get('/', [ThrottleAttributeController::class, 'index']);
        $this->assertContains(ThrottleRequests::using('uploads'), $route->controllerMiddleware());

        $route = Route::get('/', [ThrottleAttributeController::class, 'show']);
        $this->assertNotContains(ThrottleRequests::using('uploads'), $route->controllerMiddleware());
    }

    public function test_numeric_limiter_attribute_is_resolved(): void
    {
        $route = Route::get('/', [ThrottleAttributeWithNumericLimiterController::class, 'index']);

        $this->assertEquals([
            ThrottleRequests::with(60, 1),
        ], $route->controllerMiddleware());
    }

    public function test_method_level_throttle_attribute(): void
    {
        $route = Route::get('/', [ThrottleAttributeController::class, 'show']);

        $this->assertEquals([
            ThrottleRequests::using('api'),
            ThrottleRequests::with(10, 1),
        ], $route->controllerMiddleware());
    }
}

#[Throttle('api')]
#[Throttle('uploads', only: ['index'])]
class ThrottleAttributeController
{
    public function index(): void
    {
        // ...
    }

    #[Throttle(10, 1)]
    public function show(): void
    {
        // ...
    }
}

#[Throttle(60, 1)]
class ThrottleAttributeWithNumericLimiterController
{
    public function index(): void
    {
        // ...
    }
}
