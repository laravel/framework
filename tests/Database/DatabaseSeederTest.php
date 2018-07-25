<?php

use Mockery as m;
use Illuminate\Database\Seeder;

class DatabaseSeederTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCallResolveTheClassAndCallsRun()
	{
		$seeder = new Seeder;
		$seeder->setContainer($container = m::mock('Illuminate\Container\Container'));
		$output = m::mock('Symfony\Component\Console\Output\OutputInterface');
		$output->shouldReceive('writeln')->once()->andReturn('foo');
		$command = m::mock('Illuminate\Console\Command');
		$command->shouldReceive('getOutput')->once()->andReturn($output);
		$seeder->setCommand($command);
		$container->shouldReceive('make')->once()->with('ClassName')->andReturn($child = m::mock('StdClass'));
		$child->shouldReceive('setContainer')->once()->with($container)->andReturn($child);
		$child->shouldReceive('setCommand')->once()->with($command)->andReturn($child);
		$child->shouldReceive('run')->once();

		$seeder->call('ClassName');
	}


	public function testSetContainer()
	{
		$seeder = new Seeder;
		$container = m::mock('Illuminate\Container\Container');
		$this->assertEquals($seeder->setContainer($container), $seeder);
	}


	public function testSetCommand()
	{
		$seeder = new Seeder;
		$command = m::mock('Illuminate\Console\Command');
		$this->assertEquals($seeder->setCommand($command), $seeder);
	}

}
