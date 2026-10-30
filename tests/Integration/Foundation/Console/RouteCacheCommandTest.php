<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\RouteCacheCommand;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Illuminate\Tests\Integration\Generators\TestCase;
use Mockery as m;
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

    public function testItRestoresTheContainerInstanceAfterBootingAFreshApplication(): void
    {
        $this->artisan('route:cache')->assertSuccessful();

        $this->assertSame($this->app, Container::getInstance());
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

    public function testRoutesAreCachedAtomically(): void
    {
        $files = m::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('replace')->once()->with($this->app->getCachedRoutesPath(), m::type('string'))->passthru();
        $files->shouldNotReceive('put');

        $this->app->instance(RouteCacheCommand::class, new class($files) extends RouteCacheCommand
        {
            protected function getFreshApplicationRoutes()
            {
                return tap(new RouteCollection, function ($routes) {
                    $routes->add(new RoutingRoute('GET', 'posts', [RouteCacheCommandTestController::class, 'index']));
                });
            }
        });

        $this->artisan('route:cache')
            ->assertSuccessful()
            ->expectsOutputToContain('Routes cached successfully');

        $this->assertFileExists($this->app->getCachedRoutesPath());
    }
}

class RouteCacheCommandTestController extends Controller
{
    public function index()
    {
        return 'ok';
    }
}
