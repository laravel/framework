<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Routing\Controller;
use Illuminate\Support\ServiceProvider;
use Illuminate\Tests\Integration\Foundation\Console\Fixtures\AppCache;
use Illuminate\Tests\Integration\Generators\TestCase;
use LogicException;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class RouteCacheCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected $files = [
        'cache/routes-v7.php',
    ];

    protected function tearDown(): void
    {
        @unlink(__DIR__.'/Fixtures/cache/routes-v7.php');

        parent::tearDown();
    }

    public function testRoutesRemainAnalyzableAfterCaching(): void
    {
        $this->app->useBootstrapPath(__DIR__.'/Fixtures');

        $app = (static function () {
            $refresh = true;

            return require __DIR__.'/Fixtures/app.php';
        })();

        $app['router']->get('/posts', [RouteCacheCommandTestController::class, 'index'])->name('posts.index');

        $this->artisan('route:cache')
            ->assertSuccessful()
            ->expectsOutputToContain('Routes cached successfully.');

        $this->assertFileExists(__DIR__.'/Fixtures/cache/routes-v7.php');

        foreach ($app['router']->getRoutes() as $route) {
            try {
                $route->getController();
                $route->gatherMiddleware();
            } catch (LogicException $exception) {
                $this->fail(sprintf(
                    'Route [%s] is no longer analyzable after route:cache: %s',
                    $route->uri(),
                    $exception->getMessage(),
                ));
            }
        }
    }

    public function testOptimizeCanRunTasksThatAnalyzeRoutesAfterRouteCache(): void
    {
        $this->withoutDeprecationHandling();

        $this->app->useBootstrapPath(__DIR__.'/Fixtures');

        $app = (static function () {
            $refresh = true;

            return require __DIR__.'/Fixtures/app.php';
        })();

        $app['router']->get('/posts', [RouteCacheCommandTestController::class, 'index'])->name('posts.index');

        $this->app->register(RouteAnalysisOptimizeServiceProvider::class);

        $this->artisan('optimize', ['--except' => 'config,events,views'])
            ->assertSuccessful()
            ->expectsOutputToContain('route analysis succeeded');
    }
}

class RouteCacheCommandTestController extends Controller
{
    public function index(): string
    {
        return 'posts';
    }
}

class RouteAnalysisOptimizeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            RouteAnalysisOptimizeCommand::class,
        ]);

        $this->optimizes(
            optimize: 'test:route-analysis',
            key: 'route-analysis',
        );
    }
}

class RouteAnalysisOptimizeCommand extends Command
{
    protected $signature = 'test:route-analysis';

    protected $description = 'Analyze routes after route:cache';

    public function handle(): int
    {
        foreach (AppCache::$app['router']->getRoutes() as $route) {
            $route->gatherMiddleware();
        }

        $this->components->info('route analysis succeeded');

        return self::SUCCESS;
    }
}
