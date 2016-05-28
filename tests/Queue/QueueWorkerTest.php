<?php

use Mockery as m;

class QueueWorkerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testJobIsPoppedOffQueueAndProcessed()
    {
        $worker = $this->getMockBuilder('Illuminate\Queue\Worker')->setMethods(['process'])->setConstructorArgs([$manager = m::mock('Illuminate\Queue\QueueManager')])->getMock();
        $manager->shouldReceive('connection')->once()->with('connection')->andReturn($connection = m::mock('StdClass'));
        $manager->shouldReceive('getName')->andReturn('connection');
        $job = m::mock('Illuminate\Contracts\Queue\Job');
        $connection->shouldReceive('pop')->once()->with('queue')->andReturn($job);
        $worker->expects($this->once())->method('process')->with($this->equalTo('connection'), $this->equalTo($job), $this->equalTo(0), $this->equalTo(0));

        $worker->runNextJob('connection', 'queue');
    }

    public function testJobIsPoppedOffFirstQueueInListAndProcessed()
    {
        $worker = $this->getMockBuilder('Illuminate\Queue\Worker')->setMethods(['process'])->setConstructorArgs([$manager = m::mock('Illuminate\Queue\QueueManager')])->getMock();
        $manager->shouldReceive('connection')->once()->with('connection')->andReturn($connection = m::mock('StdClass'));
        $manager->shouldReceive('getName')->andReturn('connection');
        $job = m::mock('Illuminate\Contracts\Queue\Job');
        $connection->shouldReceive('pop')->once()->with('queue1')->andReturn(null);
        $connection->shouldReceive('pop')->once()->with('queue2')->andReturn($job);
        $worker->expects($this->once())->method('process')->with($this->equalTo('connection'), $this->equalTo($job), $this->equalTo(0), $this->equalTo(0));

        $worker->runNextJob('connection', 'queue1,queue2');
    }

    public function testWorkerSleepsIfNoJobIsPresentAndSleepIsEnabled()
    {
        $worker = $this->getMockBuilder('Illuminate\Queue\Worker')->setMethods(['process', 'sleep'])->setConstructorArgs([$manager = m::mock('Illuminate\Queue\QueueManager')])->getMock();
        $manager->shouldReceive('connection')->once()->with('connection')->andReturn($connection = m::mock('StdClass'));
        $connection->shouldReceive('pop')->once()->with('queue')->andReturn(null);
        $worker->expects($this->never())->method('process');
        $worker->expects($this->once())->method('sleep')->with($this->equalTo(3));

        $worker->runNextJob('connection', 'queue', 0, 3);
    }

    public function testWorkerLogsJobToFailedQueueIfMaxTriesHasBeenExceeded()
    {
        $worker = new Illuminate\Queue\Worker(m::mock('Illuminate\Queue\QueueManager'), $failer = m::mock('Illuminate\Queue\Failed\FailedJobProviderInterface'));
        $job = m::mock('Illuminate\Contracts\Queue\Job');
        $job->shouldReceive('attempts')->once()->andReturn(10);
        $job->shouldReceive('getQueue')->once()->andReturn('queue');
        $job->shouldReceive('getRawBody')->once()->andReturn('body');
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('failed')->once();
        $failer->shouldReceive('log')->once()->with('connection', 'queue', 'body');

        $worker->process('connection', $job, 3, 0);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testJobIsReleasedWhenExceptionIsThrown()
    {
        $worker = new Illuminate\Queue\Worker(m::mock('Illuminate\Queue\QueueManager'));
        $job = m::mock('Illuminate\Contracts\Queue\Job');
        $job->shouldReceive('fire')->once()->andReturnUsing(function () {
            throw new RuntimeException;
        });
        $job->shouldReceive('isDeleted')->once()->andReturn(false);
        $job->shouldReceive('release')->once()->with(5);

        $worker->process('connection', $job, 0, 5);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testJobIsNotReleasedWhenExceptionIsThrownButJobIsDeleted()
    {
        $worker = new Illuminate\Queue\Worker(m::mock('Illuminate\Queue\QueueManager'));
        $job = m::mock('Illuminate\Contracts\Queue\Job');
        $job->shouldReceive('fire')->once()->andReturnUsing(function () {
            throw new RuntimeException;
        });
        $job->shouldReceive('isDeleted')->once()->andReturn(true);
        $job->shouldReceive('release')->never();

        $worker->process('connection', $job, 0, 5);
    }
}
