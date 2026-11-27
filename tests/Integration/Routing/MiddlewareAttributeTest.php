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

    public function test_attribute_middleware_accepts_an_array(): void
    {
        $route = Route::get('/', [MiddlewareAttributeArrayController::class, 'index']);
        $this->assertEquals([
            'auth',
            'verified',
            'only-index',
            'also-index',
            'can:update,post',
        ], $route->controllerMiddleware());

        $route = Route::get('/', [MiddlewareAttributeArrayController::class, 'show']);
        $this->assertEquals([
            'auth',
            'verified',
        ], $route->controllerMiddleware());
    }
}

#[Middleware(['auth', 'verified'])]
#[Middleware(['only-index'], only: ['index'])]
class MiddlewareAttributeArrayController
{
    #[Middleware(['also-index', 'can:update,post'])]
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
