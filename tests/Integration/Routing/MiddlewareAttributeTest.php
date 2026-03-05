<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class MiddlewareAttributeTest extends TestCase
{
    public function test_attribute_middleware_is_respected(): void
    {
        $route = Route::get('/', [MiddlewareAttributeController::class, 'index']);
        $this->assertEquals([
            'all',
            'only-index',
            'also-index',
        ], $route->controllerMiddleware());

        $route = Route::get('/', [MiddlewareAttributeController::class, 'show']);
        $this->assertEquals([
            'all',
            'except-index',
        ], $route->controllerMiddleware());
    }

    public function test_attribute_middleware_supports_arrays(): void
    {
        $route = Route::get('/', [MiddlewareArrayAttributeController::class, 'index']);
        $this->assertEquals([
            'all',
            'all-array-one',
            'all-array-two',
            'also-index-array-one',
            'also-index-array-two',
        ], $route->controllerMiddleware());

        $route = Route::get('/', [MiddlewareArrayAttributeController::class, 'show']);
        $this->assertEquals([
            'all',
            'all-array-one',
            'all-array-two',
        ], $route->controllerMiddleware());
    }
}

#[Middleware('all')]
#[Middleware('only-index', only: ['index'])]
#[Middleware('except-index', except: ['index'])]
class MiddlewareAttributeController
{
    #[Middleware('also-index')]
    public function index(): void
    {
        // ...
    }

    public function show(): void
    {
        // ...
    }
}

#[Middleware('all')]
#[Middleware(['all-array-one', 'all-array-two'])]
class MiddlewareArrayAttributeController
{
    #[Middleware(['also-index-array-one', 'also-index-array-two'], only: ['index'])]
    public function index(): void
    {
        // ...
    }

    public function show(): void
    {
        // ...
    }
}
