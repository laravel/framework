<?php

use Mockery as m;
use Illuminate\Queue\Worker;

class QueueWorkerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testJobIsPoppedOffQueueAndProcessed()
	{
		$worker = $this->getMock('Illuminate\Queue\Worker', array('process'), array($manager = m::mock('Illuminate\Queue\QueueManager')));
		$manager->shouldReceive('connection')->once()->with('connection')->andReturn($connection = m::mock('StdClass'));
		$job = m::mock('Illuminate\Queue\Jobs\Job');
		$connection->shouldReceive('pop')->once()->with('queue')->andReturn($job);
		$worker->expects($this->once())->method('process')->with($this->equalTo($job));

		$worker->pop('connection', 'queue');
	}


	public function testJobIsPoppedOffFirstQueueInListAndProcessed()
	{
		$worker = $this->getMock('Illuminate\Queue\Worker', array('process'), array($manager = m::mock('Illuminate\Queue\QueueManager')));
		$manager->shouldReceive('connection')->once()->with('connection')->andReturn($connection = m::mock('StdClass'));
		$job = m::mock('Illuminate\Queue\Jobs\Job');
		$connection->shouldReceive('pop')->once()->with('queue1')->andReturn(null);
		$connection->shouldReceive('pop')->once()->with('queue2')->andReturn($job);
		$worker->expects($this->once())->method('process')->with($this->equalTo($job));

		$worker->pop('connection', 'queue1,queue2');
	}


	public function testWorkerSleepsIfNoJobIsPresentAndSleepIsEnabled()
	{
		$worker = $this->getMock('Illuminate\Queue\Worker', array('process', 'sleep'), array($manager = m::mock('Illuminate\Queue\QueueManager')));
		$manager->shouldReceive('connection')->once()->with('connection')->andReturn($connection = m::mock('StdClass'));
		$connection->shouldReceive('pop')->once()->with('queue')->andReturn(null);
		$worker->expects($this->never())->method('process');
		$worker->expects($this->once())->method('sleep')->with($this->equalTo(1));

		$worker->pop('connection', 'queue', 0, 128, true);
	}


	public function testProcessFiresJobAndAutoDeletesIfTrue()
	{
		$worker = new Illuminate\Queue\Worker(m::mock('Illuminate\Queue\QueueManager'));
		$job = m::mock('Illuminate\Queue\Jobs\Job');
		$job->shouldReceive('fire')->once();
		$job->shouldReceive('autoDelete')->once()->andReturn(true);
		$job->shouldReceive('delete')->once();

		$worker->process($job, 0);
	}


	public function testProcessFiresJobAndDoesntCallDeleteIfJobDoesntAutoDelete()
	{
		$worker = new Illuminate\Queue\Worker(m::mock('Illuminate\Queue\QueueManager'));
		$job = m::mock('Illuminate\Queue\Jobs\Job');
		$job->shouldReceive('fire')->once();
		$job->shouldReceive('autoDelete')->once()->andReturn(false);
		$job->shouldReceive('delete')->never();

		$worker->process($job, 0);
	}


	/**
	 * @expectedException RuntimeException
	 */
	public function testJobIsReleasedWhenExceptionIsThrown()
	{
		$worker = new Illuminate\Queue\Worker(m::mock('Illuminate\Queue\QueueManager'));
		$job = m::mock('Illuminate\Queue\Jobs\Job');
		$job->shouldReceive('fire')->once()->andReturnUsing(function() { throw new RuntimeException; });
		$job->shouldReceive('isDeleted')->once()->andReturn(false);
		$job->shouldReceive('release')->once()->with(5);

		$worker->process($job, 5);
	}


	/**
	 * @expectedException RuntimeException
	 */
	public function testJobIsNotReleasedWhenExceptionIsThrownButJobIsDeleted()
	{
		$worker = new Illuminate\Queue\Worker(m::mock('Illuminate\Queue\QueueManager'));
		$job = m::mock('Illuminate\Queue\Jobs\Job');
		$job->shouldReceive('fire')->once()->andReturnUsing(function() { throw new RuntimeException; });
		$job->shouldReceive('isDeleted')->once()->andReturn(true);
		$job->shouldReceive('release')->never();

		$worker->process($job, 5);
	}

}