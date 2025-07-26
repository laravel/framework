<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\AttributeRouteController;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\AttributeRouteRegistrar;
use Illuminate\Routing\Attributes\Get;
use Illuminate\Routing\Attributes\Group;
use Illuminate\Routing\Attributes\Post;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Mockery as m;
use PHPUnit\Framework\TestCase;

// --- Test Controllers (Stubs) ---
class BasicController implements AttributeRouteController
{
    #[Get('/get', name: 'get')]
    public function get()
    {
        return 'get success';
    }
    #[Post('/post')]
    public function post()
    {
        return 'post success';
    }
}
#[Group(prefix: 'group', name: 'group.')]
class GroupController implements AttributeRouteController
{
    #[Get('/route', name: 'route')]
    public function route()
    {
        return 'grouped route';
    }
}

// --- Test Class ---
class AttributeRoutingTest extends TestCase
{
    protected $container;
    protected $router;
    protected $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = Container::setInstance(new Container);

        $this->router = new Router(new Dispatcher, $this->container);
        $this->container->instance('router', $this->router);

        Facade::setFacadeApplication($this->container);

        $request = Request::create('http://example.com');
        $this->container->instance('url', new UrlGenerator(
            $this->router->getRoutes(), $request
        ));

        $appMock = m::mock(Application::class);
        $appMock->shouldReceive('basePath')->andReturn('');
        $this->container->instance(Application::class, $appMock);

        $this->registrar = new AttributeRouteRegistrar($appMock, $this->router);

        $this->registrar->registerControllerRoutes(BasicController::class);
        $this->registrar->registerControllerRoutes(GroupController::class);

        $this->router->getRoutes()->refreshNameLookups();
    }

    protected function tearDown(): void
    {
        m::close();
        Facade::clearResolvedInstances();
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_it_registers_and_accesses_a_basic_get_route(): void
    {
        $request = Request::create('/get', 'GET');
        $response = $this->router->dispatch($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('get success', $response->getContent());
    }

    public function test_it_registers_a_basic_post_route(): void
    {
        $request = Request::create('/post', 'POST');
        $response = $this->router->dispatch($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('post success', $response->getContent());
    }

    public function test_it_applies_a_name_to_a_route(): void
    {
        $this->assertTrue(Route::has('get'));
        $this->assertEquals('http://example.com/get', route('get'));
    }

    public function test_it_applies_group_prefix(): void
    {
        $request = Request::create('/group/route', 'GET');
        $response = $this->router->dispatch($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('grouped route', $response->getContent());
    }

    public function test_it_applies_group_name_prefix(): void
    {
        $this->assertTrue(Route::has('group.route'));
        $this->assertEquals('http://example.com/group/route', route('group.route'));
    }
}
