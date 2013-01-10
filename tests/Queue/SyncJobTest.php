<?php

use Mockery as m;

class SyncJobTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFireResolvesAndFiresJobClass()
	{
		$container = m::mock('Illuminate\Container');
		$job = new Illuminate\Queue\Jobs\SyncJob($container, 'Foo', 'data');
		$handler = m::mock('StdClass');
		$container->shouldReceive('make')->once()->with('Foo')->andReturn($handler);
		$handler->shouldReceive('fire')->once()->with($job, 'data');

		$job->fire();
	}

}