<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Auth\Access\Gate;
use Illuminate\Routing\Controller;
use Illuminate\Container\Container;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class AuthorizesResourcesTest extends PHPUnit_Framework_TestCase
{
    public function testIndexMethod()
    {
        $gate = $this->getGate();

        $gate->shouldReceive('authorize')->with('view', ['App\User'])->once();

        $controller = new AuthorizesResourcesController($this->request('index'));
    }

    public function testCreateMethod()
    {
        $gate = $this->getGate();

        $gate->shouldReceive('authorize')->with('create', ['App\User'])->once();

        $controller = new AuthorizesResourcesController($this->request('create'));
    }

    public function testStoreMethod()
    {
        $gate = $this->getGate();

        $gate->shouldReceive('authorize')->with('create', ['App\User'])->once();

        $controller = new AuthorizesResourcesController($this->request('store'));
    }

    public function testShowMethod()
    {
        $gate = $this->getGate();

        $gate->shouldReceive('authorize')->with('view', ['user'])->once();

        $controller = new AuthorizesResourcesController($this->request('show'));
    }

    public function testEditMethod()
    {
        $gate = $this->getGate();

        $gate->shouldReceive('authorize')->with('update', ['user'])->once();

        $controller = new AuthorizesResourcesController($this->request('edit'));
    }

    public function testUpdateMethod()
    {
        $gate = $this->getGate();

        $gate->shouldReceive('authorize')->with('update', ['user'])->once();

        $controller = new AuthorizesResourcesController($this->request('update'));
    }

    public function testDeleteMethod()
    {
        $gate = $this->getGate();

        $gate->shouldReceive('authorize')->with('delete', ['user'])->once();

        $controller = new AuthorizesResourcesController($this->request('delete'));
    }

    /**
     * Get a request object, with the route pointing to the given method on the controller.
     *
     * @param  string  $method
     * @return \Illuminate\Http\Request
     */
    protected function request($method)
    {
        return Request::create('foo', 'GET')->setRouteResolver(function () use ($method) {
            $action = ['uses' => 'AuthorizesResourcesController@'.$method];

            $action['controller'] = $action['uses'];

            return new Route('GET', 'foo', $action);
        });
    }

    public function getGate()
    {
        $container = new Container;
        Container::setInstance($container);

        $gate = m::mock(new Gate($container, function () { return (object) ['id' => 1]; }));
        $container->instance(GateContract::class, $gate);

        return $gate;
    }
}

class AuthorizesResourcesController extends Controller
{
    use AuthorizesResources;

    public function __construct(Request $request)
    {
        $this->authorizeResource('App\User', 'user', $request);
    }
}
