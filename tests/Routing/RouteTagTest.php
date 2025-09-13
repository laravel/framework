<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;

class RouteTagTest extends TestCase
{
    protected $router;

    protected function setUp(): void
    {
        parent::setUp();
        $container = new Container();
        $this->router = new Router(new Dispatcher($container), $container);
    }

    public function testCanAddSingleTag()
    {
        $route = new Route(['GET'], '/test', []);
        $route->tag('api');

        $this->assertTrue($route->hasTag('api'));
        $this->assertEquals(['api'], $route->getTags());
    }

    public function testCanAddMultipleTags()
    {
        $route = new Route(['GET'], '/test', []);
        $route->tags(['api', 'public']);

        $this->assertTrue($route->hasTag('api'));
        $this->assertTrue($route->hasTag('public'));
        $this->assertEquals(['api', 'public'], $route->getTags());
    }

    public function testCanChainTagMethods()
    {
        $route = new Route(['GET'], '/test', []);
        $result = $route->tag('api')->tags(['public', 'v1']);

        $this->assertSame($route, $result);
        $this->assertEquals(['api', 'public', 'v1'], $route->getTags());
    }

    public function testTagsAreUnique()
    {
        $route = new Route(['GET'], '/test', []);
        $route->tag('api')->tag('api');

        $this->assertEquals(['api'], $route->getTags());
    }

    public function testHasAnyTag()
    {
        $route = new Route(['GET'], '/test', []);
        $route->tags(['api', 'public']);

        $this->assertTrue($route->hasAnyTag(['api', 'admin']));
        $this->assertFalse($route->hasAnyTag(['admin', 'private']));
    }

    public function testHasAllTags()
    {
        $route = new Route(['GET'], '/test', []);
        $route->tags(['api', 'public', 'v1']);

        $this->assertTrue($route->hasAllTags(['api', 'public']));
        $this->assertFalse($route->hasAllTags(['api', 'admin']));
    }

    public function testCanRemoveTag()
    {
        $route = new Route(['GET'], '/test', []);
        $route->tags(['api', 'public'])->withoutTag('api');

        $this->assertFalse($route->hasTag('api'));
        $this->assertTrue($route->hasTag('public'));
        $this->assertEquals(['public'], $route->getTags());
    }

    public function testGroupRoutesInheritTags()
    {
        $this->router->group(['tags' => ['api']], function ($router) {
            $router->get('/users', function () {
                return 'users';
            })->name('users.index');
            $router->get('/posts', function () {
                return 'posts';
            })->name('posts.index');
        });

        $routes = $this->router->getRoutes();
        $allRoutes = $routes->getRoutes();
        $usersRoute = null;
        $postsRoute = null;

        foreach ($allRoutes as $route) {
            if ($route->uri === 'users') {
                $usersRoute = $route;
            }
            if ($route->uri === 'posts') {
                $postsRoute = $route;
            }
        }

        $this->assertNotNull($usersRoute, 'Users route not found');
        $this->assertNotNull($postsRoute, 'Posts route not found');

        $this->assertTrue($usersRoute->hasTag('api'));
        $this->assertTrue($postsRoute->hasTag('api'));
        $this->assertEquals(['api'], $usersRoute->getTags());
        $this->assertEquals(['api'], $postsRoute->getTags());
    }

    public function testNestedGroupTagsAreMerged()
    {
        $this->router->group(['tags' => ['api']], function ($router) {
            $router->group(['tags' => ['v1']], function ($router) {
                $router->get('/v1/users', function () {
                    return 'users';
                });
            });
            $router->group(['tags' => ['v2']], function ($router) {
                $router->get('/v2/users', function () {
                    return 'users';
                });
            });
        });

        $routes = $this->router->getRoutes()->getRoutes();
        $v1Route = null;
        $v2Route = null;

        foreach ($routes as $route) {
            if ($route->uri === 'v1/users') {
                $v1Route = $route;
            }
            if ($route->uri === 'v2/users') {
                $v2Route = $route;
            }
        }

        $this->assertNotNull($v1Route, 'V1 route not found');
        $this->assertNotNull($v2Route, 'V2 route not found');

        $this->assertTrue($v1Route->hasTag('api'));
        $this->assertTrue($v1Route->hasTag('v1'));
        $this->assertTrue($v2Route->hasTag('api'));
        $this->assertTrue($v2Route->hasTag('v2'));
        $this->assertFalse($v1Route->hasTag('v2'));
        $this->assertFalse($v2Route->hasTag('v1'));
    }

    public function testRoutesCanAddAdditionalTagsToGroupTags()
    {
        $this->router->group(['tags' => ['api']], function ($router) {
            $router->get('/admin/users', function () {
                return 'users';
            })->tag('admin');
            $router->get('/public/posts', function () {
                return 'posts';
            })->tag('public');
        });

        $routes = $this->router->getRoutes()->getRoutes();
        $usersRoute = null;
        $postsRoute = null;

        foreach ($routes as $route) {
            if ($route->uri === 'admin/users') {
                $usersRoute = $route;
            }
            if ($route->uri === 'public/posts') {
                $postsRoute = $route;
            }
        }

        $this->assertNotNull($usersRoute, 'Users route not found');
        $this->assertNotNull($postsRoute, 'Posts route not found');

        $this->assertTrue($usersRoute->hasTag('api'));
        $this->assertTrue($usersRoute->hasTag('admin'));
        $this->assertTrue($postsRoute->hasTag('api'));
        $this->assertTrue($postsRoute->hasTag('public'));
        $this->assertFalse($usersRoute->hasTag('public'));
        $this->assertFalse($postsRoute->hasTag('admin'));
    }

    public function testResourceRoutesCanHaveTags()
    {
        $this->router->resource('users', 'UserController')->tags(['api', 'admin']);

        $routes = $this->router->getRoutes()->getRoutes();
        $indexRoute = null;
        $showRoute = null;
        $createRoute = null;

        foreach ($routes as $route) {
            if ($route->uri === 'users') {
                $indexRoute = $route;
            }
            if ($route->uri === 'users/{user}') {
                $showRoute = $route;
            }
            if ($route->uri === 'users/create') {
                $createRoute = $route;
            }
        }

        $this->assertNotNull($indexRoute);
        $this->assertNotNull($showRoute);
        $this->assertNotNull($createRoute);

        $this->assertTrue($indexRoute->hasTag('api'));
        $this->assertTrue($indexRoute->hasTag('admin'));
        $this->assertTrue($showRoute->hasTag('api'));
        $this->assertTrue($showRoute->hasTag('admin'));
        $this->assertTrue($createRoute->hasTag('api'));
        $this->assertTrue($createRoute->hasTag('admin'));
    }

    public function testResourceRoutesCanHaveTagsChained()
    {
        $this->router->resource('posts', 'PostController')
            ->tag('api')
            ->tags(['v1', 'public']);

        $routes = $this->router->getRoutes()->getRoutes();
        $indexRoute = null;

        foreach ($routes as $route) {
            if ($route->uri === 'posts' && in_array('GET', $route->methods)) {
                $indexRoute = $route;
                break;
            }
        }

        $this->assertNotNull($indexRoute);
        $this->assertTrue($indexRoute->hasTag('api'));
        $this->assertTrue($indexRoute->hasTag('v1'));
        $this->assertTrue($indexRoute->hasTag('public'));
        $this->assertEquals(['api', 'v1', 'public'], $indexRoute->getTags());
    }

    public function testGroupTagsAreUnique()
    {
        $this->router->group(['tags' => ['api', 'api', 'admin']], function ($router) {
            $router->get('/unique/users', function () {
                return 'users';
            })->tag('admin');
        });

        $routes = $this->router->getRoutes()->getRoutes();
        $route = null;

        foreach ($routes as $r) {
            if ($r->uri === 'unique/users') {
                $route = $r;
                break;
            }
        }

        $this->assertNotNull($route, 'Route not found');
        $tags = $route->getTags();

        $this->assertEquals(array_values(array_unique($tags)), array_values($tags));
        $this->assertCount(2, $tags);
        $this->assertContains('api', $tags);
        $this->assertContains('admin', $tags);
    }
}
