<?php

namespace Illuminate\Tests\Routing;

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

class RoutePrefixTest extends TestCase
{
    /**
     * @var $router Router
     */
    protected $router;
    protected $routes = [
        'posts',
        'posts/create',
        'posts/{post}',
        'posts/{post}/edit',
    ];

    public function setUp()
    {
        parent::setUp();

        $this->router = new Router(m::mock(Dispatcher::class), Container::getInstance());
    }

    public function testCommonResource()
    {
        $this->router->resource('posts', 'PostController');

        $allRoutes = collect($this->router->getRoutes())->pluck('uri')->toArray();


        foreach ($allRoutes as $uri) {

            $this->assertContains($uri, $this->routes);
        }
    }

    public function testEmptyPrefix()
    {
        $this->router->prefix('posts')->group(function (Router $router) {

            $router->resource('/', 'PostController');
        });


        $allRoutes = collect($this->router->getRoutes())->pluck('uri')->toArray();


        foreach ($allRoutes as $uri) {

            $this->assertContains($uri, $this->routes);
        }
    }
}
