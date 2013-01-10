<?php

use Mockery as m;
use Illuminate\Database\Console\SeedCommand;

class DatabaseSeedCommandTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFireCallsSeederWithProperConnectionAndPath()
	{
		$events = m::mock('Illuminate\Events\Dispatcher');
		$events->shouldReceive('listen')->once()->with('illuminate.seeding', m::type('Closure'));

		$command = new SeedCommand(
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			$seeder = m::mock('Illuminate\Database\Seeder'),
			$events,
			'path'
		);

		$connection = m::mock('Illuminate\Database\Connection');
		$resolver->shouldReceive('connection')->once()->with(null)->andReturn($connection);
		$seeder->shouldReceive('seed')->once()->with($connection, 'path');

		$command->run(new Symfony\Component\Console\Input\ArrayInput(array()), new Symfony\Component\Console\Output\NullOutput);
	}


	public function testFireCallsSeederWithProperConnectionAndPathWhenConnectionIsSpecified()
	{
		$events = m::mock('Illuminate\Events\Dispatcher');
		$events->shouldReceive('listen')->once()->with('illuminate.seeding', m::type('Closure'));

		$command = new SeedCommand(
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			$seeder = m::mock('Illuminate\Database\Seeder'),
			$events,
			'path'
		);

		$connection = m::mock('Illuminate\Database\Connection');
		$resolver->shouldReceive('connection')->once()->with('foo')->andReturn($connection);
		$seeder->shouldReceive('seed')->once()->with($connection, 'path');

		$command->run(new Symfony\Component\Console\Input\ArrayInput(array('--database' => 'foo')), new Symfony\Component\Console\Output\NullOutput);
	}

}