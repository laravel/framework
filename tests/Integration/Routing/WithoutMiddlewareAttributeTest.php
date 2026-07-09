<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Routing\Attributes\Controllers\WithoutMiddleware;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class WithoutMiddlewareAttributeTest extends TestCase
{
    public function test_attribute_without_middleware_is_respected(): void
    {
        $route = Route::get('/', [WithoutMiddlewareAttributeController::class, 'index']);
        $this->assertEquals([
            'all',
            'only-index',
            'also-index',
        ], $route->excludedMiddleware());

        $route = Route::get('/', [WithoutMiddlewareAttributeController::class, 'show'])->withoutMiddleware('merged');
        $this->assertEquals([
            'merged',
            'all',
            'except-index',
        ], $route->excludedMiddleware());

        $route = Route::get('/', [ChildWithoutMiddlewareAttributeController::class, 'index']);
        $this->assertEquals([
            'all',
            'only-index',
            'also-index',
        ], $route->excludedMiddleware());

        $route = Route::get('/', [ChildWithoutMiddlewareAttributeController::class, 'show'])->withoutMiddleware('merged');
        $this->assertEquals([
            'merged',
            'all',
            'except-index',
        ], $route->excludedMiddleware());
    }
}

#[WithoutMiddleware('all')]
#[WithoutMiddleware('only-index', only: ['index'])]
#[WithoutMiddleware('except-index', except: ['index'])]
class WithoutMiddlewareAttributeController
{
    #[WithoutMiddleware('also-index')]
    public function index(): void
    {
        // ...
    }

    public function show(): void
    {
        // ...
    }
}


class ChildWithoutMiddlewareAttributeController extends WithoutMiddlewareAttributeController
{
}
