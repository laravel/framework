<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Console\Application;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Router;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RouteListCommandTest extends TestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application(
            $laravel = new \Illuminate\Foundation\Application(__DIR__),
            m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing',
        );

        $router = new Router(m::mock('Illuminate\Events\Dispatcher'));

        $kernel = new class($laravel, $router) extends Kernel
        {
            protected $middlewareGroups = [
                'web' => ['Middleware 1', 'Middleware 2', 'Middleware 5'],
                'auth' => ['Middleware 3', 'Middleware 4'],
            ];

            protected $middlewarePriority = [
                'Middleware 1',
                'Middleware 4',
                'Middleware 2',
                'Middleware 3',
            ];
        };

        $kernel->prependToMiddlewarePriority('Middleware 5');

        $laravel->instance(Kernel::class, $kernel);

        $router->get('/example', function () {
            return 'Hello World';
        })->middleware('exampleMiddleware');

        $router->get('/sub-example', function () {
            return 'Hello World';
        })->domain('sub')
            ->middleware('exampleMiddleware');

        $router->get('/example-group', function () {
            return 'Hello Group';
        })->middleware(['web', 'auth']);

        $command = new RouteListCommand($router);
        $command->setLaravel($laravel);

        $this->app->addCommands([$command]);
    }

    public function testNoMiddlewareIfNotVerbose()
    {
        $this->app->call('route:list');
        $output = $this->app->output();

        $this->assertStringNotContainsString('exampleMiddleware', $output);
    }

    public function testSortRouteListAsc()
    {
        $this->app->call('route:list', ['--json' => true, '--sort' => 'domain,uri']);
        $output = $this->app->output();

        $routes = json_decode($output, true);

        $this->assertCount(3, $routes);
        $this->assertEquals('example', $routes[0]['uri']);
        $this->assertEquals('example-group', $routes[1]['uri']);
        $this->assertEquals('sub-example', $routes[2]['uri']);

        foreach ($routes as $route) {
            $this->assertArrayHasKey('path', $route);
            $this->assertStringContainsString('RouteListCommandTest.php:', $route['path']);
        }
    }

    public function testSortRouteListDesc()
    {
        $this->app->call('route:list', ['--json' => true, '--sort' => 'domain,uri', '--reverse' => true]);
        $output = $this->app->output();

        $routes = json_decode($output, true);

        $this->assertCount(3, $routes);
        $this->assertEquals('sub-example', $routes[0]['uri']);
        $this->assertEquals('example-group', $routes[1]['uri']);
        $this->assertEquals('example', $routes[2]['uri']);

        foreach ($routes as $route) {
            $this->assertArrayHasKey('path', $route);
            $this->assertStringContainsString('RouteListCommandTest.php:', $route['path']);
        }
    }

    public function testSortRouteListDefault()
    {
        $this->app->call('route:list', ['--json' => true]);
        $output = $this->app->output();

        $routes = json_decode($output, true);

        $this->assertCount(3, $routes);
        $this->assertEquals('example', $routes[0]['uri']);
        $this->assertEquals('example-group', $routes[1]['uri']);
        $this->assertEquals('sub-example', $routes[2]['uri']);

        foreach ($routes as $route) {
            $this->assertArrayHasKey('path', $route);
            $this->assertStringContainsString('RouteListCommandTest.php:', $route['path']);
        }
    }

    public function testSortRouteListPrecedence()
    {
        $this->app->call('route:list', ['--json' => true, '--sort' => 'definition']);
        $output = $this->app->output();

        $routes = json_decode($output, true);

        $this->assertCount(3, $routes);
        $this->assertEquals('example', $routes[0]['uri']);
        $this->assertEquals('sub-example', $routes[1]['uri']);
        $this->assertEquals('example-group', $routes[2]['uri']);

        foreach ($routes as $route) {
            $this->assertArrayHasKey('path', $route);
            $this->assertStringContainsString('RouteListCommandTest.php:', $route['path']);
        }
    }

    public function testMiddlewareGroupsAssignmentInCli()
    {
        $this->app->call('route:list', ['-v' => true]);
        $output = $this->app->output();

        $this->assertStringContainsString('exampleMiddleware', $output);
        $this->assertStringContainsString('web', $output);
        $this->assertStringContainsString('auth', $output);

        $this->assertStringNotContainsString('Middleware 1', $output);
        $this->assertStringNotContainsString('Middleware 2', $output);
        $this->assertStringNotContainsString('Middleware 3', $output);
        $this->assertStringNotContainsString('Middleware 4', $output);
        $this->assertStringNotContainsString('Middleware 5', $output);
    }

    public function testMiddlewareGroupsExpandInCliIfVeryVerbose()
    {
        $this->app->call('route:list', ['-vv' => true]);
        $output = $this->app->output();

        $this->assertStringContainsString('exampleMiddleware', $output);
        $this->assertStringContainsString('Middleware 1', $output);
        $this->assertStringContainsString('Middleware 2', $output);
        $this->assertStringContainsString('Middleware 3', $output);
        $this->assertStringContainsString('Middleware 4', $output);
        $this->assertStringContainsString('Middleware 5', $output);

        $this->assertStringNotContainsString('web', $output);
        $this->assertStringNotContainsString('auth', $output);
    }

    public function testMiddlewareGroupsAssignmentInJson()
    {
        $this->app->call('route:list', ['--json' => true, '-v' => true]);
        $output = $this->app->output();

        $this->assertStringContainsString('exampleMiddleware', $output);
        $this->assertStringContainsString('web', $output);
        $this->assertStringContainsString('auth', $output);

        $this->assertStringNotContainsString('Middleware 1', $output);
        $this->assertStringNotContainsString('Middleware 2', $output);
        $this->assertStringNotContainsString('Middleware 3', $output);
        $this->assertStringNotContainsString('Middleware 4', $output);
        $this->assertStringNotContainsString('Middleware 5', $output);
    }

    public function testMiddlewareGroupsExpandInJsonIfVeryVerbose()
    {
        $this->app->call('route:list', ['--json' => true, '-vv' => true]);
        $output = $this->app->output();

        $this->assertStringContainsString('exampleMiddleware', $output);
        $this->assertStringContainsString('Middleware 1', $output);
        $this->assertStringContainsString('Middleware 2', $output);
        $this->assertStringContainsString('Middleware 3', $output);
        $this->assertStringContainsString('Middleware 4', $output);
        $this->assertStringContainsString('Middleware 5', $output);

        $this->assertStringNotContainsString('web', $output);
        $this->assertStringNotContainsString('auth', $output);
    }

    public function testMiddlewareGroupsExpandCorrectlySortedIfVeryVerbose()
    {
        $this->app->call('route:list', ['--json' => true, '-vv' => true]);
        $output = $this->app->output();

        $routes = json_decode($output, true);

        $this->assertCount(3, $routes);
        $this->assertEquals('example', $routes[0]['uri']);
        $this->assertEquals(['exampleMiddleware'], $routes[0]['middleware']);
        $this->assertEquals('example-group', $routes[1]['uri']);
        $this->assertEquals(['Middleware 5', 'Middleware 1', 'Middleware 4', 'Middleware 2', 'Middleware 3'], $routes[1]['middleware']);
        $this->assertEquals('sub-example', $routes[2]['uri']);
        $this->assertEquals(['exampleMiddleware'], $routes[2]['middleware']);
    }

    public function testFilterByMiddleware()
    {
        $this->app->call('route:list', ['--json' => true, '-v' => true, '--middleware' => 'auth']);
        $output = $this->app->output();

        $routes = json_decode($output, true);

        $this->assertCount(1, $routes);
        $this->assertEquals('example-group', $routes[0]['uri']);
        $this->assertEquals(['web', 'auth'], $routes[0]['middleware']);
        $this->assertStringContainsString('RouteListCommandTest.php:', $routes[0]['path']);
    }

    public function testFilterWithoutMiddlewareGroup()
    {
        $this->app->call('route:list', ['--json' => true, '-v' => true, '--without-middleware' => 'auth']);
        $output = $this->app->output();

        $routes = json_decode($output, true);

        $this->assertCount(2, $routes);
        $this->assertEquals('example', $routes[0]['uri']);
        $this->assertEquals(['exampleMiddleware'], $routes[0]['middleware']);
        $this->assertStringContainsString('RouteListCommandTest.php:', $routes[0]['path']);
        $this->assertEquals('sub-example', $routes[1]['uri']);
        $this->assertEquals(['exampleMiddleware'], $routes[1]['middleware']);
        $this->assertStringContainsString('RouteListCommandTest.php:', $routes[1]['path']);
    }

    public function testFilterWithoutMiddleware()
    {
        $this->app->call('route:list', ['--json' => true, '-v' => true, '--without-middleware' => 'exampleMiddleware']);
        $output = $this->app->output();

        $routes = json_decode($output, true);       

        $this->assertCount(1, $routes);
        $this->assertEquals('example-group', $routes[0]['uri']);
        $this->assertEquals(['web', 'auth'], $routes[0]['middleware']);
        $this->assertStringContainsString('RouteListCommandTest.php:', $routes[0]['path']);
    }

    public function testClosureRouteShowsPathInCli()
    {
        RouteListCommand::resolveTerminalWidthUsing(fn () => 200);

        $this->app->call('route:list');
        $output = $this->app->output();

        $this->assertStringContainsString('RouteListCommandTest.php:', $output);

        RouteListCommand::resolveTerminalWidthUsing(null);
    }

    public function testControllerRoutePathIsNull()
    {
        $laravel = new \Illuminate\Foundation\Application(__DIR__);
        $router = new Router(m::mock('Illuminate\Events\Dispatcher'));

        $kernel = new class($laravel, $router) extends Kernel
        {
            protected $middlewareGroups = [];
        };

        $laravel->instance(Kernel::class, $kernel);

        $router->get('/controller-route', [RouteListCommandTestController::class, 'index']);

        $command = new RouteListCommand($router);
        $command->setLaravel($laravel);

        $app = new Application(
            $laravel,
            m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing',
        );
        $app->addCommands([$command]);
        $app->call('route:list', ['--json' => true]);
        $output = $app->output();

        $routes = json_decode($output, true);

        $this->assertCount(1, $routes);
        $this->assertNull($routes[0]['path']);
    }
}

class RouteListCommandTestController
{
    public function index()
    {
        return 'Hello World';
    }
}
