<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Illuminate\Tests\Integration\Generators\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class RouteCacheCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected $files = [
        'bootstrap/cache/routes-v7.php',
    ];

    public function testItRestoresTheFacadeApplicationAfterBootingAFreshApplication(): void
    {
        $this->artisan('route:cache')->assertSuccessful();

        $this->assertSame($this->app, Facade::getFacadeApplication());
    }

    public function testItLeavesTheFacadeRootsPointingAtTheCurrentApplication(): void
    {
        $this->artisan('route:cache')->assertSuccessful();

        $this->assertSame($this->app['router'], Route::getFacadeRoot());
    }

    public function testRoutesRemainAnalyzableAfterCaching(): void
    {
        Route::get('/posts', [RouteCacheCommandTestController::class, 'index']);

        $this->artisan('route:cache')->assertSuccessful();

        $route = collect(Route::getRoutes())->first(fn ($route) => $route->uri() === 'posts');

        $this->assertNotNull($route, 'The registered route is no longer reachable through the route facade.');
        $this->assertInstanceOf(RouteCacheCommandTestController::class, $route->getController());
    }
}

class RouteCacheCommandTestController extends Controller
{
    public function index()
    {
        return 'ok';
    }
}
