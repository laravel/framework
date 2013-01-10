<?php

use Mockery as m;
use Illuminate\Events\Dispatcher;

class DispatcherTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicEventExecution()
	{
		$d = new Dispatcher;
		$d->listen('foo', function($e) { $e->foo = 'bar'; });
		$e = $d->fire('foo', array('baz' => 'boom'));
		$this->assertEquals('bar', $e->foo);
	}


	public function testContainerResolutionOfEventHandlers()
	{
		$d = new Dispatcher($container = m::mock('Illuminate\Container'));
		$container->shouldReceive('make')->once()->with('FooHandler')->andReturn($handler = m::mock('StdClass'));
		$handler->shouldReceive('onFooEvent')->once()->with(m::type('Illuminate\Events\Event'));
		$d->listen('foo', 'FooHandler@onFooEvent');
		$d->fire('foo', new Illuminate\Events\Event);
	}


	public function testContainerResolutionOfEventHandlersWithDefaultMethods()
	{
		$d = new Dispatcher($container = m::mock('Illuminate\Container'));
		$container->shouldReceive('make')->once()->with('FooHandler')->andReturn($handler = m::mock('StdClass'));
		$handler->shouldReceive('handle')->once()->with(m::type('Illuminate\Events\Event'));
		$d->listen('foo', 'FooHandler');
		$d->fire('foo', new Illuminate\Events\Event);
	}

}