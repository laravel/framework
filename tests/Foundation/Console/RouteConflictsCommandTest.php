<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Console\Application;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Console\RouteConflictsCommand;
use Illuminate\Routing\Router;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RouteConflictsCommandTest extends TestCase
{
    protected Application $app;

    protected Router $router;

    protected function tearDown(): void
    {
        m::close();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application(
            $laravel = new \Illuminate\Foundation\Application(__DIR__),
            m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing',
        );

        $this->router = new Router(m::mock('Illuminate\Events\Dispatcher'));

        $command = new RouteConflictsCommand($this->router);
        $command->setLaravel($laravel);

        $this->app->addCommands([$command]);
    }

    public function testNoConflictsDetected()
    {
        $this->router->get('/users', fn () => 'Users list');
        $this->router->get('/posts', fn () => 'Posts list');
        $this->router->post('/comments', fn () => 'Create comment');

        $exitCode = $this->app->call('route:conflicts');

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No route conflicts detected', $this->app->output());
    }

    public function testDetectsParameterVsLiteralConflict()
    {
        $this->router->get('/users/{id}', fn () => 'Show user');
        $this->router->get('/users/admin', fn () => 'Admin panel');

        $exitCode = $this->app->call('route:conflicts');
        $output = $this->app->output();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Route conflicts found', $output);
        $this->assertStringContainsString('users/{id}', $output);
        $this->assertStringContainsString('users/admin', $output);
    }

    public function testNoConflictWithParameterConstraints()
    {
        $this->router->get('/users/{id}', fn () => 'Show user')->where('id', '[0-9]+');
        $this->router->get('/users/admin', fn () => 'Admin panel');

        $exitCode = $this->app->call('route:conflicts');

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No route conflicts detected', $this->app->output());
    }

    public function testDetectsParameterVsParameterConflict()
    {
        $this->router->get('/posts/{slug}', fn () => 'Show post by slug');
        $this->router->get('/posts/{id}', fn () => 'Show post by id');

        $exitCode = $this->app->call('route:conflicts');
        $output = $this->app->output();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Route conflicts found', $output);
        $this->assertStringContainsString('posts/{slug}', $output);
        $this->assertStringContainsString('posts/{id}', $output);
    }

    public function testNoConflictWithDifferentHttpMethods()
    {
        $this->router->get('/users/{id}', fn () => 'Show user');
        $this->router->post('/users/{id}', fn () => 'Update user');
        $this->router->delete('/users/{id}', fn () => 'Delete user');

        $exitCode = $this->app->call('route:conflicts');

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No route conflicts detected', $this->app->output());
    }

    public function testDetectsConflictWithSharedHttpMethods()
    {
        $this->router->match(['GET', 'POST'], '/users/{id}', fn () => 'User action');
        $this->router->post('/users/admin', fn () => 'Admin action');

        $exitCode = $this->app->call('route:conflicts');

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Route conflicts found', $this->app->output());
    }

    public function testNoConflictWithDifferentSegmentCounts()
    {
        $this->router->get('/users', fn () => 'Users list');
        $this->router->get('/users/{id}', fn () => 'Show user');
        $this->router->get('/users/{id}/posts', fn () => 'User posts');

        $exitCode = $this->app->call('route:conflicts');

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No route conflicts detected', $this->app->output());
    }

    public function testJsonOutputFormat()
    {
        $this->router->get('/products/{id}', fn () => 'Show product')->name('products.show');
        $this->router->get('/products/featured', fn () => 'Featured products')->name('products.featured');

        $exitCode = $this->app->call('route:conflicts', ['--json' => true]);
        $output = $this->app->output();

        $this->assertEquals(1, $exitCode);

        preg_match('/(\[.*\])/s', $output, $matches);
        $json = json_decode($matches[1], true);

        $this->assertIsArray($json);
        $this->assertNotEmpty($json);
        $this->assertArrayHasKey('methods', $json[0]);
        $this->assertArrayHasKey('earlier', $json[0]);
        $this->assertArrayHasKey('later', $json[0]);
        $this->assertEquals('products/{id}', $json[0]['earlier']['uri']);
        $this->assertEquals('products/featured', $json[0]['later']['uri']);
        $this->assertEquals('products.show', $json[0]['earlier']['name']);
        $this->assertEquals('products.featured', $json[0]['later']['name']);
    }

    public function testCliOutputFormat()
    {
        $this->router->get('/articles/{slug}', fn () => 'Show article');
        $this->router->get('/articles/latest', fn () => 'Latest articles');

        $exitCode = $this->app->call('route:conflicts');
        $output = $this->app->output();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('[GET,HEAD]', $output);
        $this->assertStringContainsString('Earlier:', $output);
        $this->assertStringContainsString('Later:', $output);
        $this->assertStringContainsString('articles/{slug}', $output);
        $this->assertStringContainsString('articles/latest', $output);
        $this->assertStringContainsString('Closure', $output);
    }

    public function testDetectsMultipleConflicts()
    {
        $this->router->get('/users/{id}', fn () => 'User');
        $this->router->get('/users/admin', fn () => 'Admin');
        $this->router->get('/posts/{slug}', fn () => 'Post');
        $this->router->get('/posts/featured', fn () => 'Featured');

        $exitCode = $this->app->call('route:conflicts', ['--json' => true]);
        $output = $this->app->output();

        $this->assertEquals(1, $exitCode);

        preg_match('/(\[.*\])/s', $output, $matches);
        $json = json_decode($matches[1], true);

        $this->assertCount(2, $json);

        $uris = array_column($json, 'earlier');
        $uris = array_column($uris, 'uri');

        $this->assertContains('users/{id}', $uris);
        $this->assertContains('posts/{slug}', $uris);
    }

    public function testConflictDetectionWithNamedRoutes()
    {
        $this->router->get('/api/users/{id}', fn () => 'API user')->name('api.users.show');
        $this->router->get('/api/users/me', fn () => 'Current user')->name('api.users.me');

        $exitCode = $this->app->call('route:conflicts', ['--json' => true]);
        $output = $this->app->output();

        $this->assertEquals(1, $exitCode);

        preg_match('/(\[.*\])/s', $output, $matches);
        $json = json_decode($matches[1], true);

        $this->assertEquals('api.users.show', $json[0]['earlier']['name']);
        $this->assertEquals('api.users.me', $json[0]['later']['name']);
    }

    public function testNoConflictWithComplexNonOverlappingUris()
    {
        $this->router->get('/users/{userId}/posts/{postId}', fn () => 'User post');
        $this->router->get('/users/{userId}/comments/{commentId}', fn () => 'User comment');
        $this->router->get('/admin/{adminId}/posts/{postId}', fn () => 'Admin post');

        $exitCode = $this->app->call('route:conflicts');

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No route conflicts detected', $this->app->output());
    }

    public function testConflictWithControllerRoutes()
    {
        $this->router->get('/dashboard/{section}', 'DashboardController@show');
        $this->router->get('/dashboard/overview', 'DashboardController@overview');

        $exitCode = $this->app->call('route:conflicts');
        $output = $this->app->output();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('DashboardController', $output);
        $this->assertStringContainsString('dashboard/{section}', $output);
        $this->assertStringContainsString('dashboard/overview', $output);
    }

    public function testLiteralRouteFirstNoConflict()
    {
        $this->router->get('/users/admin', fn () => 'Admin panel');
        $this->router->get('/users/{id}', fn () => 'Show user');

        $exitCode = $this->app->call('route:conflicts');

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No route conflicts detected', $this->app->output());
    }
}
