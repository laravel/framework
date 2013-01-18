<?php

use Mockery as m;
use Illuminate\Routing\Generators\ControllerGenerator;

class RoutingControllerGeneratorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFullControllerCanBeCreated()
	{
		$gen = new ControllerGenerator($files = m::mock('Illuminate\Filesystem\Filesystem[put]'));
		$controller = file_get_contents(__DIR__.'/fixtures/controller.php');
		$files->shouldReceive('put')->once()->with(__DIR__.'/FooController.php', $controller);
		$gen->make('FooController', __DIR__);
	}


	public function testOnlyPartialControllerCanBeCreated()
	{
		$gen = new ControllerGenerator($files = m::mock('Illuminate\Filesystem\Filesystem[put]'));
		$controller = file_get_contents(__DIR__.'/fixtures/only_controller.php');
		$files->shouldReceive('put')->once()->with(__DIR__.'/FooController.php', $controller);
		$gen->make('FooController', __DIR__, array('only' => array('index', 'show')));
	}


	public function testExceptPartialControllerCanBeCreated()
	{
		$gen = new ControllerGenerator($files = m::mock('Illuminate\Filesystem\Filesystem[put]'));
		$controller = file_get_contents(__DIR__.'/fixtures/except_controller.php');
		$files->shouldReceive('put')->once()->with(__DIR__.'/FooController.php', $controller);
		$gen->make('FooController', __DIR__, array('except' => array('index', 'show')));
	}

}