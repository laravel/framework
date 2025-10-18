<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Attributes\Authorize;
use Illuminate\Auth\Attributes\Gate;
use Illuminate\Auth\Middleware\AuthorizeFromAttributes;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route as RoutingRoute;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AuthorizationAttributesTest extends TestCase
{
    public function testAuthorizeAttributeCanBeCreated()
    {
        $authorize = new Authorize('update', 'post');

        $this->assertEquals('update', $authorize->ability);
        $this->assertEquals('post', $authorize->arguments);
    }

    public function testGateAttributeCanBeCreated()
    {
        $gate = new Gate('admin');

        $this->assertEquals('admin', $gate->ability);
        $this->assertEquals([], $gate->arguments);
    }

    public function testAuthorizeAttributeCanBeAppliedToClass()
    {
        $reflection = new ReflectionClass(TestControllerWithClassAuthorization::class);
        $attributes = $reflection->getAttributes(Authorize::class);

        $this->assertCount(1, $attributes);
        
        $authorize = $attributes[0]->newInstance();
        $this->assertEquals('admin', $authorize->ability);
    }

    public function testAuthorizeAttributeCanBeAppliedToMethod()
    {
        $reflection = new ReflectionClass(TestControllerWithMethodAuthorization::class);
        $method = $reflection->getMethod('update');
        $attributes = $method->getAttributes(Authorize::class);

        $this->assertCount(1, $attributes);
        
        $authorize = $attributes[0]->newInstance();
        $this->assertEquals('update', $authorize->ability);
        $this->assertEquals('post', $authorize->arguments);
    }

    public function testGateAttributeCanBeAppliedToMethod()
    {
        $reflection = new ReflectionClass(TestControllerWithGateAttribute::class);
        $method = $reflection->getMethod('adminPanel');
        $attributes = $method->getAttributes(Gate::class);

        $this->assertCount(1, $attributes);
        
        $gate = $attributes[0]->newInstance();
        $this->assertEquals('admin', $gate->ability);
    }

    public function testMultipleAttributesCanBeApplied()
    {
        $reflection = new ReflectionClass(TestControllerWithMultipleAttributes::class);
        
        // Class-level attributes
        $classAuthorize = $reflection->getAttributes(Authorize::class);
        $classGate = $reflection->getAttributes(Gate::class);
        
        $this->assertCount(1, $classAuthorize);
        $this->assertCount(1, $classGate);

        // Method-level attributes
        $method = $reflection->getMethod('sensitiveAction');
        $methodAuthorize = $method->getAttributes(Authorize::class);
        
        $this->assertCount(1, $methodAuthorize);
    }

    public function testRouteAutomaticallyDetectsAuthorizationAttributes()
    {
        $container = new \Illuminate\Container\Container();
        $route = new RoutingRoute(['GET'], '/test', [
            'uses' => TestControllerWithMethodAuthorization::class.'@update'
        ]);
        $route->setContainer($container);
        
        $middleware = $route->controllerMiddleware();
        
        $this->assertContains(AuthorizeFromAttributes::class, $middleware);
    }

    public function testRouteWithoutAttributesHasNoAuthorizationMiddleware()
    {
        $container = new \Illuminate\Container\Container();
        $route = new RoutingRoute(['GET'], '/test', [
            'uses' => TestControllerWithoutAttributes::class.'@index'
        ]);
        $route->setContainer($container);
        
        $middleware = $route->controllerMiddleware();
        
        $this->assertNotContains(AuthorizeFromAttributes::class, $middleware);
    }

    public function testAuthorizationMiddlewareProcessesAttributes()
    {
        $gateMock = $this->createMock(GateContract::class);
        $gateMock->expects($this->once())
                 ->method('authorize')
                 ->with('admin', []);

        $middleware = new AuthorizeFromAttributes($gateMock);
        
        $request = new Request();
        $container = new \Illuminate\Container\Container();
        $route = new RoutingRoute(['GET'], '/admin', ['uses' => TestControllerWithClassAuthorization::class.'@index']);
        $route->setContainer($container);
        $controller = new TestControllerWithClassAuthorization();
        $route->controller = $controller;
        $request->setRouteResolver(fn() => $route);

        $middleware->handle($request, function ($req) {
            return 'OK';
        });
    }
}

#[Authorize('admin')]
class TestControllerWithClassAuthorization extends Controller
{
    public function index()
    {
        return 'admin panel';
    }
}

class TestControllerWithMethodAuthorization extends Controller
{
    #[Authorize('update', 'post')]
    public function update()
    {
        return 'updated';
    }

    public function index()
    {
        return 'index';
    }
}

class TestControllerWithGateAttribute extends Controller
{
    #[Gate('admin')]
    public function adminPanel()
    {
        return 'admin panel';
    }
}

#[Authorize('user')]
#[Gate('verified')]
class TestControllerWithMultipleAttributes extends Controller
{
    #[Authorize('delete', 'post')]
    public function sensitiveAction()
    {
        return 'sensitive';
    }
}

class TestControllerWithoutAttributes extends Controller
{
    public function index()
    {
        return 'no attributes';
    }
}