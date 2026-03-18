<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Routing\Attributes\Controllers\WithoutMiddleware;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class WithoutMiddlewareAttributeTest extends TestCase
{
    public function test_without_middleware_attribute_excludes_class_level_middleware(): void
    {
        $route = Route::get('/', [WithoutMiddlewareAttributeController::class, 'index']);
        $route->controllerMiddleware();

        $this->assertContains('SomeMiddleware', $route->excludedMiddleware());
    }

    public function test_without_middleware_attribute_with_only_option(): void
    {
        $route = Route::get('/', [WithoutMiddlewareAttributeController::class, 'index']);
        $route->controllerMiddleware();

        $this->assertContains('OnlyIndexMiddleware', $route->excludedMiddleware());
    }

    public function test_without_middleware_attribute_only_does_not_apply_to_other_methods(): void
    {
        $route = Route::get('/', [WithoutMiddlewareAttributeController::class, 'show']);
        $route->controllerMiddleware();

        $this->assertNotContains('OnlyIndexMiddleware', $route->excludedMiddleware());
    }

    public function test_without_middleware_attribute_method_level(): void
    {
        $route = Route::get('/', [WithoutMiddlewareAttributeController::class, 'index']);
        $route->controllerMiddleware();

        $this->assertContains('MethodLevelMiddleware', $route->excludedMiddleware());
    }

    public function test_without_middleware_attribute_method_level_does_not_apply_to_other_methods(): void
    {
        $route = Route::get('/', [WithoutMiddlewareAttributeController::class, 'show']);
        $route->controllerMiddleware();

        $this->assertNotContains('MethodLevelMiddleware', $route->excludedMiddleware());
    }
}

#[WithoutMiddleware('SomeMiddleware')]
#[WithoutMiddleware('OnlyIndexMiddleware', only: ['index'])]
class WithoutMiddlewareAttributeController
{
    #[WithoutMiddleware('MethodLevelMiddleware')]
    public function index(): void
    {
        //
    }

    public function show(): void
    {
        //
    }
}
