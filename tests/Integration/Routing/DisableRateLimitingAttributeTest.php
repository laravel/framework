<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Routing\Attributes\Controllers\DisableRateLimiting;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class DisableRateLimitingAttributeTest extends TestCase
{
    public function test_disable_rate_limiting_excludes_throttle_middleware_for_all_methods(): void
    {
        $route = Route::get('/', [DisableRateLimitingController::class, 'index']);
        $route->controllerMiddleware();
        $this->assertContains(ThrottleRequests::class, $route->excludedMiddleware());

        $route = Route::get('/', [DisableRateLimitingController::class, 'show']);
        $route->controllerMiddleware();
        $this->assertContains(ThrottleRequests::class, $route->excludedMiddleware());
    }

    public function test_disable_rate_limiting_with_only_option(): void
    {
        $route = Route::get('/', [DisableRateLimitingOnlyController::class, 'index']);
        $route->controllerMiddleware();

        $this->assertContains(ThrottleRequests::class, $route->excludedMiddleware());
    }

    public function test_disable_rate_limiting_only_does_not_apply_to_other_methods(): void
    {
        $route = Route::get('/', [DisableRateLimitingOnlyController::class, 'show']);
        $route->controllerMiddleware();

        $this->assertNotContains(ThrottleRequests::class, $route->excludedMiddleware());
    }

    public function test_disable_rate_limiting_method_level(): void
    {
        $route = Route::get('/', [DisableRateLimitingMethodController::class, 'index']);
        $route->controllerMiddleware();

        $this->assertContains(ThrottleRequests::class, $route->excludedMiddleware());
    }

    public function test_disable_rate_limiting_method_level_does_not_apply_to_other_methods(): void
    {
        $route = Route::get('/', [DisableRateLimitingMethodController::class, 'show']);
        $route->controllerMiddleware();

        $this->assertNotContains(ThrottleRequests::class, $route->excludedMiddleware());
    }
}

#[DisableRateLimiting]
class DisableRateLimitingController
{
    public function index(): void
    {
        //
    }

    public function show(): void
    {
        //
    }
}

#[DisableRateLimiting(only: ['index'])]
class DisableRateLimitingOnlyController
{
    public function index(): void
    {
        //
    }

    public function show(): void
    {
        //
    }
}

class DisableRateLimitingMethodController
{
    #[DisableRateLimiting]
    public function index(): void
    {
        //
    }

    public function show(): void
    {
        //
    }
}
