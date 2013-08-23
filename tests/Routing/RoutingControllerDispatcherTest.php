<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Controller;
use Illuminate\Container\Container;
use Illuminate\Routing\ControllerDispatcher;

class RoutingControllerDispatcherTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicDispatchToMethod()
	{
		$request = Request::create('controller');
		$route = new Route(array('GET'), 'controller', array('uses' => function() {}));
		$route->bind($request);
		$dispatcher = new ControllerDispatcher(m::mock('Illuminate\Routing\RouteFiltererInterface'), new Container);
		$response = $dispatcher->dispatch($route, $request, 'ControllerDispatcherTestControllerStub', 'getIndex');
		$this->assertEquals('getIndex', $response);
	}

}


class ControllerDispatcherTestControllerStub extends Controller {

	public function getIndex()
	{
		return __FUNCTION__;
	}

	public function getFoo()
	{
		return __FUNCTION__;
	}

}