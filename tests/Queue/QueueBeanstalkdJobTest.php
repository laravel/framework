<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueueBeanstalkdJobTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getPheanstalkJob()->shouldReceive('getData')->once()->andReturn(json_encode(['job' => 'foo', 'data' => ['data']]));
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('stdClass'));
        $handler->shouldReceive('fire')->once()->with($job, ['data']);

        $job->fire();
    }

    public function testFailedProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getPheanstalkJob()->shouldReceive('getData')->once()->andReturn(json_encode(['job' => 'foo', 'data' => ['data']]));
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('Illuminate\Tests\Queue\BeanstalkdJobTestFailedTest'));
        $handler->shouldReceive('failed')->once()->with(['data'], m::type('Exception'));

        $job->failed(new \Exception);
    }

    public function testDeleteRemovesTheJobFromBeanstalkd()
    {
        $job = $this->getJob();
        $job->getPheanstalk()->shouldReceive('delete')->once()->with($job->getPheanstalkJob());

        $job->delete();
    }

    public function testReleaseProperlyReleasesJobOntoBeanstalkd()
    {
        $job = $this->getJob();
        $job->getPheanstalk()->shouldReceive('release')->once()->with($job->getPheanstalkJob(), \Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, 0);

        $job->release();
    }

    public function testBuryProperlyBuryTheJobFromBeanstalkd()
    {
        $job = $this->getJob();
        $job->getPheanstalk()->shouldReceive('bury')->once()->with($job->getPheanstalkJob());

        $job->bury();
    }

    public function testRetryDelayCallsHandlerWithMethodDefined()
    {
        $job = $this->getJob();
        $handler = $this->getMockBuilder('stdClass')->setMethods(['retryDelay'])->getMock();

        $job->getPheanstalkJob()->shouldReceive('getData')->once()->andReturn(json_encode(['job' => 'foo', 'data' => ['data']]));
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler);
        $handler->expects($this->once())->method('retryDelay')->with($job, ['data'])->willReturn(10);

        $retryDelay = $job->retryDelay();

        $this->assertEquals(10, $retryDelay);
    }

    public function testRetryDelayDoesNotCallHandlerWithoutMethodDefined()
    {
        $job = $this->getJob();
        $job->getPheanstalkJob()->shouldReceive('getData')->once()->andReturn(json_encode(['job' => 'foo', 'data' => ['data']]));
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('stdClass'));
        $handler->shouldNotReceive('retryDelay');

        $retryDelay = $job->retryDelay();

        $this->assertEquals(0, $retryDelay);
    }

    protected function getJob()
    {
        return new \Illuminate\Queue\Jobs\BeanstalkdJob(
            m::mock('Illuminate\Container\Container'),
            m::mock('Pheanstalk\Pheanstalk'),
            m::mock('Pheanstalk\Job'),
            'connection-name',
            'default'
        );
    }
}

class BeanstalkdJobTestFailedTest
{
    public function failed(array $data)
    {
        //
    }
}
