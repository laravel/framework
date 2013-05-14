<?php

use Mockery as m;

class RoutingMakeControllerCommandTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testGeneratorIsCalledWithProperOptions()
	{
		$command = new Illuminate\Routing\Console\MakeControllerCommand($gen = m::mock('Illuminate\Routing\Generators\ControllerGenerator'), __DIR__);
		$gen->shouldReceive('make')->once()->with('FooController', __DIR__, array('only' => array(), 'except' => array()));
		$this->runCommand($command, array('name' => 'FooController'));
	}


	public function testGeneratorIsCalledWithProperOptionsForExceptAndOnly()
	{
		$command = new Illuminate\Routing\Console\MakeControllerCommand($gen = m::mock('Illuminate\Routing\Generators\ControllerGenerator'), __DIR__);
		$gen->shouldReceive('make')->once()->with('FooController', __DIR__.'/foo/bar', array('only' => array('foo', 'bar'), 'except' => array('baz', 'boom')));
		$command->setLaravel(array('path.base' => __DIR__.'/foo'));
		$this->runCommand($command, array('name' => 'FooController', '--only' => 'foo,bar', '--except' => 'baz,boom', '--path' => 'bar'));
	}


	public function runCommand($command, $input = array(), $output = null)
	{
		$output = $output ?: new Symfony\Component\Console\Output\NullOutput;

		return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), $output);
	}

}