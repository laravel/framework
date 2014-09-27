<?php

use Mockery as m;

class QueueIronJobTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFireProperlyCallsTheJobHandler()
	{
		$job = $this->getJob();
		$job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('StdClass'));
		$handler->shouldReceive('fire')->once()->with($job, ['data']);

		$job->fire();
	}


	public function testDeleteRemovesTheJobFromIron()
	{
		$job = $this->getJob();
		$job->getIron()->shouldReceive('deleteMessage')->once()->with('default', 1);

		$job->delete();
	}


	public function testDeleteNoopsOnPushedQueues()
	{
		$job = new Illuminate\Queue\Jobs\IronJob(
			m::mock('Illuminate\Container\Container'),
			m::mock('Illuminate\Queue\IronQueue'),
			(object) ['id' => 1, 'body' => json_encode(['job' => 'foo', 'data' => ['data']]), 'timeout' => 60, 'pushed' => true],
			'default'
		);
		$job->getIron()->shouldReceive('deleteMessage')->never();

		$job->delete();
	}


	public function testReleaseProperlyReleasesJobOntoIron()
	{
		$job = $this->getJob();
		$job->getIron()->shouldReceive('deleteMessage')->once();
		$job->getIron()->shouldReceive('recreate')->once()->with(json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 2, 'queue' => 'default']), 'default', 5);

		$job->release(5);
	}


	protected function getJob()
	{
		return new Illuminate\Queue\Jobs\IronJob(
			m::mock('Illuminate\Container\Container'),
			m::mock('Illuminate\Queue\IronQueue'),
			(object) ['id' => 1, 'body' => json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 1, 'queue' => 'default']), 'timeout' => 60]
		);
	}

}
