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
		$handler->shouldReceive('fire')->once()->with($job, array('data'));

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
			m::mock('IronMQ'),
			(object) array('id' => 1, 'body' => json_encode(array('job' => 'foo', 'data' => array('data'))), 'timeout' => 60, 'pushed' => true),
			'default'
		);
		$job->getIron()->shouldReceive('deleteMessage')->never();

		$job->delete();
	}


	public function testReleaseProperlyReleasesJobOntoIron()
	{
		$job = $this->getJob();
		$job->getIron()->shouldReceive('releaseMessage')->once()->with('default', 1, 5);

		$job->release(5);
	}


	public function testReleaseProperlyCreatesNewJobOnPushedQueues()
	{
		$job = $this->getJob(true);
		$payload = $job->getIronJob()->body;

		$job->getContainer()->shouldReceive('make')->once()->with('queue')->andReturn($QueueManager = m::mock('Illuminate\Queue\QueueManager'));
		$QueueManager->shouldReceive('connection')->once()->with('iron')->andReturn($IronQueue = m::mock('Illuminate\Queue\IronQueue'));
		$IronQueue->shouldReceive('later')->once()->with(5, $payload['job'], $payload['data'], 'default', 2);

		$job->release(5);
	}


	public function testAttemptsReturnsAttemptFromPayloadOnPushedQueues()
	{
		$job = $this->getJob(true, 8);
		$payload = $job->getIronJob()->body;

		$this->assertEquals(8, $job->attempts());
	}


	protected function getJob($pushed = false, $attempt = null)
	{
		$body = array('job' => 'foo', 'data' => array('data'));

		if( ! is_null($attempt))
		{
			$body['attempt'] = $attempt;
		}

		return new Illuminate\Queue\Jobs\IronJob(
			m::mock('Illuminate\Container\Container'),
			m::mock('IronMQ'),
			(object) array('id' => 1, 'body' => json_encode($body), 'timeout' => 60),
			'default',
			$pushed
		);
	}

}