<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Routing\Attributes\Controllers\Middleware as MiddlewareAttribute;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class ControllerMiddlewareMergingTest extends TestCase
{
    public function test_attribute_middleware_is_merged_with_has_middleware(): void
    {
        $route = Route::get('/', [HasMiddlewareWithAttributeController::class, 'index']);
        $this->assertEquals([
            'static-all',
            'static-only-index',
            'attribute-all',
            'attribute-method-index',
        ], $route->controllerMiddleware());

        $route = Route::get('/', [HasMiddlewareWithAttributeController::class, 'show']);
        $this->assertEquals([
            'static-all',
            'static-except-index',
            'attribute-all',
            'attribute-except-index',
        ], $route->controllerMiddleware());
    }

    public function test_attribute_middleware_is_merged_with_legacy_controller(): void
    {
        $route = Route::get('/', [LegacyControllerWithAttributeController::class, 'index']);
        $this->assertEquals([
            'legacy',
            'attribute-all',
        ], $route->controllerMiddleware());
    }

    public function test_plain_attribute_controller_is_not_affected(): void
    {
        $route = Route::get('/', [PlainAttributeOnlyController::class, 'index']);
        $this->assertEquals([
            'attribute-all',
            'attribute-method-index',
        ], $route->controllerMiddleware());

        $route = Route::get('/', [PlainAttributeOnlyController::class, 'show']);
        $this->assertEquals([
            'attribute-all',
        ], $route->controllerMiddleware());
    }

    public function test_attribute_middleware_ordering_follows_primary_source(): void
    {
        $route = Route::get('/', [HasMiddlewareWithAttributeController::class, 'index']);
        $middleware = $route->controllerMiddleware();

        $this->assertLessThan(
            array_search('attribute-all', $middleware),
            array_search('static-all', $middleware),
        );
    }

    public function test_duplicate_middleware_is_deduplicated_via_gather(): void
    {
        $route = Route::get('/', [DuplicateMiddlewareController::class, 'index']);
        $gathered = $route->gatherMiddleware();

        $this->assertSame(array_values(array_unique($gathered)), array_values($gathered));
    }
}

#[MiddlewareAttribute('attribute-all')]
#[MiddlewareAttribute('attribute-except-index', except: ['index'])]
class HasMiddlewareWithAttributeController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('static-all'),
            (new Middleware('static-only-index'))->only('index'),
            (new Middleware('static-except-index'))->except('index'),
        ];
    }

    #[MiddlewareAttribute('attribute-method-index')]
    public function index(): void
    {
        //
    }

    public function show(): void
    {
        //
    }
}

#[MiddlewareAttribute('attribute-all')]
class LegacyControllerWithAttributeController extends Controller
{
    public function __construct()
    {
        $this->middleware('legacy');
    }

    public function index(): void
    {
        //
    }
}

#[MiddlewareAttribute('attribute-all')]
class PlainAttributeOnlyController
{
    #[MiddlewareAttribute('attribute-method-index')]
    public function index(): void
    {
        //
    }

    public function show(): void
    {
        //
    }
}

#[MiddlewareAttribute('shared')]
class DuplicateMiddlewareController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('shared'),
        ];
    }

    public function index(): void
    {
        //
    }
}
