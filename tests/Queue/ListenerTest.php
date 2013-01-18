<?php

use Mockery as m;
use Illuminate\Queue\Listener;

class ListenerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testListenerProcessesJobsUntilMemoryIsExceeded()
	{
		$l = m::mock('Illuminate\Queue\Listener[process,memoryExceeded,stop]');
		$l->setManager(m::mock('Illuminate\Queue\QueueManager'));
		$l->getManager()->shouldReceive('connection')->with('foo')->andReturn($queue = m::mock('StdClass'));
		$queue->shouldReceive('pop')->twice()->with('queue')->andReturn($job = m::mock('Illuminate\Queue\Jobs\Job'));
		$l->shouldReceive('process')->twice()->with($job, 1);
		$l->shouldReceive('memoryExceeded')->with(128)->andReturn(false, true);
		$l->shouldReceive('stop')->once();

		$l->listen('foo', 'queue', 1, 128);
	}


	public function testListenerSleepsWhenNoJobToProcess()
	{
		$l = m::mock('Illuminate\Queue\Listener[process,memoryExceeded,stop,sleep]');
		$l->setManager(m::mock('Illuminate\Queue\QueueManager'));
		$l->getManager()->shouldReceive('connection')->with('foo')->andReturn($queue = m::mock('StdClass'));
		$queue->shouldReceive('pop')->with('queue')->andReturn(null, $job = m::mock('Illuminate\Queue\Jobs\Job'));
		$l->shouldReceive('sleep')->once()->with(1);
		$l->shouldReceive('process')->once()->with($job, 1);
		$l->shouldReceive('memoryExceeded')->with(128)->andReturn(false, true);
		$l->shouldReceive('stop')->once();

		$l->listen('foo', 'queue', 1, 128);
	}


	public function testProcessJobFiresJob()
	{
		$l = $this->getListener();
		$job = m::mock('Illuminate\Queue\Jobs\Job');
		$job->shouldReceive('fire')->once();
		$job->shouldReceive('autoDelete')->once()->andReturn(false);

		$l->process($job, 0);
	}


	public function testProcessJobFiresJobAndDeletesItWhenAutoDeleting()
	{
		$l = $this->getListener();
		$job = m::mock('Illuminate\Queue\Jobs\Job');
		$job->shouldReceive('fire')->once();
		$job->shouldReceive('autoDelete')->once()->andReturn(true);
		$job->shouldReceive('delete')->once();

		$l->process($job, 0);
	}


	/**
	 * @expectedException RuntimeException
	 */
	public function testJobIsReleasedWhenExceptionOccurs()
	{
		$l = $this->getListener();
		$job = m::mock('Illuminate\Queue\Jobs\Job');
		$job->shouldReceive('fire')->once()->andReturnUsing(function()
		{
			throw new RuntimeException;
		});
		$job->shouldReceive('release')->once()->with(1);

		$l->process($job, 1);
	}


	protected function getListener()
	{
		return new Listener(m::mock('Illuminate\Queue\QueueManager'));
	}

}