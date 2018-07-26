<?php

use Mockery as m;

class QueueSyncJobTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFireResolvesAndFiresJobClass()
	{
		$container = m::mock('Illuminate\Container\Container');
		$job = new Illuminate\Queue\Jobs\SyncJob($container, 'Foo', '"data"');
		$handler = m::mock('StdClass');
		$container->shouldReceive('make')->once()->with('Foo')->andReturn($handler);
		$handler->shouldReceive('fire')->once()->with($job, 'data');

		$job->fire();
	}


	public function testClosuresCanBeFiredBySyncJob()
	{
		unset($_SERVER['__queue.closure']);
		$job = new Illuminate\Queue\Jobs\SyncJob(new Illuminate\Container\Container, function() { $_SERVER['__queue.closure'] = true; }, 'data');
		$job->fire();

		$this->assertTrue($_SERVER['__queue.closure']);
	}


	public function testFireResolvesAndFiresJobClassWithCorrectMethod()
	{
		$container = m::mock('Illuminate\Container\Container');
		$job = new Illuminate\Queue\Jobs\SyncJob($container, 'Foo@bar', '"data"');
		$handler = m::mock('StdClass');
		$container->shouldReceive('make')->once()->with('Foo')->andReturn($handler);
		$handler->shouldReceive('bar')->once()->with($job, 'data');

		$job->fire();
	}

}
