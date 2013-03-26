<?php

use Mockery as m;
use Illuminate\Database\Seeder;

class DatabaseSeederTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCallResolveTheClassAndCallsRun()
	{
		$seeder = new Seeder;
		$seeder->setContainer($container = m::mock('Illuminate\Container\Container'));
		$seeder->setCommand($command = m::mock('Illuminate\Console\Command'));
		$container->shouldReceive('make')->once()->with('ClassName')->andReturn($child = m::mock('StdClass'));
		$child->shouldReceive('setContainer')->once()->with($container)->andReturn($child);
		$child->shouldReceive('setCommand')->once()->with($command)->andReturn($child);
		$child->shouldReceive('run')->once();

		$seeder->call('ClassName');
	}

}