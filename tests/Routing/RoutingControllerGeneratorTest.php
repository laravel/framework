<?php

use Mockery as m;
use Illuminate\Routing\Generators\ControllerGenerator;

class RoutingControllerGeneratorTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFullControllerCanBeCreated()
	{
		$gen = new ControllerGenerator($files = m::mock('Illuminate\Filesystem\Filesystem[put]'));
		$controller = file_get_contents(__DIR__.'/fixtures/controller.php');
		$files->shouldReceive('put')->once()->andReturnUsing(function($path, $actual)
		{
			$_SERVER['__controller.actual'] = $actual;
		});
		$gen->make('FooController', __DIR__);

		$controller = preg_replace('/\s+/', '', $controller);
		$actual = preg_replace('/\s+/', '', $_SERVER['__controller.actual']);
		$this->assertEquals($controller, $actual);
	}


	public function testOnlyPartialControllerCanBeCreated()
	{
		$gen = new ControllerGenerator($files = m::mock('Illuminate\Filesystem\Filesystem[put]'));
		$controller = file_get_contents(__DIR__.'/fixtures/only_controller.php');
		$files->shouldReceive('put')->once()->andReturnUsing(function($path, $actual)
		{
			$_SERVER['__controller.actual'] = $actual;
		});
		$gen->make('FooController', __DIR__, array('only' => array('index', 'show')));

		$controller = preg_replace('/\s+/', '', $controller);
		$actual = preg_replace('/\s+/', '', $_SERVER['__controller.actual']);
		$this->assertEquals($controller, $actual);
	}


	public function testExceptPartialControllerCanBeCreated()
	{
		$gen = new ControllerGenerator($files = m::mock('Illuminate\Filesystem\Filesystem[put]'));
		$controller = file_get_contents(__DIR__.'/fixtures/except_controller.php');
		$files->shouldReceive('put')->once()->andReturnUsing(function($path, $actual)
		{
			$_SERVER['__controller.actual'] = $actual;
		});
		$gen->make('FooController', __DIR__, array('except' => array('index', 'show')));

		$controller = preg_replace('/\s+/', '', $controller);
		$actual = preg_replace('/\s+/', '', $_SERVER['__controller.actual']);
		$this->assertEquals($controller, $actual);
	}

}
