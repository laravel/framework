<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Controller;
use Illuminate\Container\Container;
use Illuminate\Routing\ControllerDispatcher;

class RoutingControllerDispatcherTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$_SERVER['ControllerDispatcherTestControllerStub'] = null;
	}


	public function tearDown()
	{
		unset($_SERVER['ControllerDispatcherTestControllerStub']);
		m::close();
	}


	public function testBasicDispatchToMethod()
	{
		$request = Request::create('controller');
		// Blank "users" Closure because we just need to stub something...
		$route = new Route(array('GET'), 'controller', array('uses' => function() {}));
		$route->bind($request);
		$dispatcher = new ControllerDispatcher(m::mock('Illuminate\Routing\RouteFiltererInterface'), new Container);

		$this->assertNull($_SERVER['ControllerDispatcherTestControllerStub']);

		$response = $dispatcher->dispatch($route, $request, 'ControllerDispatcherTestControllerStub', 'getIndex');
		$this->assertEquals('getIndex', $response);
		$this->assertEquals('setupLayout', $_SERVER['ControllerDispatcherTestControllerStub']);
	}


	public function testDispatchToMethodWithInjectedParameters()
	{
		$request = Request::create('controller/foo');
		// Blank "users" Closure because we just need to stub something...
		$route = new Route(array('GET'), 'controller/{foo}', array('uses' => function() {}));
		$route->bind($request);
		$dispatcher = new ControllerDispatcher(m::mock('Illuminate\Routing\RouteFiltererInterface'), new Container);

		$response = $dispatcher->dispatch($route, $request, 'ControllerDispatcherTestControllerStub', 'getInject');

		$this->assertEquals('foo', $response[1]);
		$this->assertEquals('stdClass', $response[0]);
	}


	public function testDispatchToMethodWithInjectedParametersInTheMiddleOfSignature()
	{
		$request = Request::create('controller/foo/bar');
		// Blank "users" Closure because we just need to stub something...
		$route = new Route(array('GET'), 'controller/{foo}/{bar}', array('uses' => function() {}));
		$route->bind($request);
		$dispatcher = new ControllerDispatcher(m::mock('Illuminate\Routing\RouteFiltererInterface'), new Container);

		$response = $dispatcher->dispatch($route, $request, 'ControllerDispatcherTestControllerStub', 'getReverseInject');

		$this->assertEquals('stdClass', $response[1]);
		$this->assertEquals('foo', $response[0]);
		$this->assertEquals('bar', $response[2]);
	}

}


class ControllerDispatcherTestControllerStub extends Controller {

	public function __construct()
	{
		// construct shouldn't affect setupLayout.
	}

	protected function setupLayout()
	{
		$_SERVER['ControllerDispatcherTestControllerStub'] = __FUNCTION__;
	}


	public function getIndex()
	{
		return __FUNCTION__;
	}


	public function getFoo()
	{
		return __FUNCTION__;
	}


	public function getInject(StdClass $class, $foo)
	{
		return [get_class($class), $foo];
	}


	public function getReverseInject($foo, StdClass $class, $bar)
	{
		return [$foo, get_class($class), $bar];
	}

}
