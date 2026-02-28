<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Routing\Controllers\Attributes\Middleware;
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
