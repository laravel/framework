<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Jobs\BeanstalkdJob;
use Mockery as m;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use PHPUnit\Framework\TestCase;
use stdClass;

class QueueBeanstalkdJobTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getPheanstalkJob()->shouldReceive('getData')->once()->andReturn(json_encode(['job' => 'foo', 'data' => ['data']]));
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock(stdClass::class));
        $handler->shouldReceive('fire')->once()->with($job, ['data']);

        $job->fire();
    }

    public function testFailProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getPheanstalkJob()->shouldReceive('getData')->andReturn(json_encode(['job' => 'foo', 'uuid' => 'test-uuid', 'data' => ['data']]));
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock(BeanstalkdJobTestFailedTest::class));
        $job->getPheanstalk()->shouldReceive('delete')->once()->with($job->getPheanstalkJob())->andReturnSelf();
        $handler->shouldReceive('failed')->once()->with(['data'], m::type(Exception::class), 'test-uuid');
        $job->getContainer()->shouldReceive('make')->once()->with(Dispatcher::class)->andReturn($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(JobFailed::class))->andReturnNull();

        $job->fail(new Exception);
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
        $job->getPheanstalk()->shouldReceive('release')->once()->with($job->getPheanstalkJob(), Pheanstalk::DEFAULT_PRIORITY, 0);

        $job->release();
    }

    public function testBuryProperlyBuryTheJobFromBeanstalkd()
    {
        $job = $this->getJob();
        $job->getPheanstalk()->shouldReceive('bury')->once()->with($job->getPheanstalkJob());

        $job->bury();
    }

    protected function getJob()
    {
        return new BeanstalkdJob(
            m::mock(Container::class),
            m::mock(Pheanstalk::class),
            m::mock(Job::class),
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
