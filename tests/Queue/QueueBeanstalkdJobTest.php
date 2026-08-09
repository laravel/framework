<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Jobs\BeanstalkdJob;
use Illuminate\Queue\Jobs\Job;
use Mockery as m;
use Pheanstalk\Contract\JobIdInterface;
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Pheanstalk;
use PHPUnit\Framework\TestCase;
use stdClass;

class QueueBeanstalkdJobTest extends TestCase
{
    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getPheanstalkJob()->expects('getData')->andReturn(json_encode(['job' => 'foo', 'data' => ['data']]));
        $handler = m::mock(stdClass::class);
        $job->getContainer()->expects('make')->with('foo')->andReturn($handler);
        $handler->expects('fire')->with($job, ['data']);

        $job->fire();
    }

    public function testFailProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getPheanstalkJob()->shouldReceive('getData')->andReturn(json_encode(['job' => 'foo', 'uuid' => 'test-uuid', 'data' => ['data']]));
        $handler = m::mock(BeanstalkdJobTestFailedTest::class);
        $job->getContainer()->expects('make')->with('foo')->andReturn($handler);
        $job->getPheanstalk()->expects('delete')->with($job->getPheanstalkJob())->andReturnSelf();
        $handler->expects('failed')->with(['data'], m::type(Exception::class), 'test-uuid', m::type(Job::class));
        $events = m::mock(Dispatcher::class);
        $job->getContainer()->expects('make')->with(Dispatcher::class)->andReturn($events);
        $events->expects('dispatch')->with(m::type(JobFailed::class))->andReturnNull();

        $job->fail(new Exception);
    }

    public function testDeleteRemovesTheJobFromBeanstalkd()
    {
        $job = $this->getJob();
        $job->getPheanstalk()->expects('delete')->with($job->getPheanstalkJob());

        $job->delete();
    }

    public function testReleaseProperlyReleasesJobOntoBeanstalkd()
    {
        $job = $this->getJob();
        $job->getPheanstalk()->expects('release')->with($job->getPheanstalkJob(), Pheanstalk::DEFAULT_PRIORITY, 0);

        $job->release();
    }

    public function testBuryProperlyBuryTheJobFromBeanstalkd()
    {
        $job = $this->getJob();
        $job->getPheanstalk()->expects('bury')->with($job->getPheanstalkJob());

        $job->bury();
    }

    protected function getJob()
    {
        return new BeanstalkdJob(
            m::mock(Container::class),
            m::mock(implode(',', [PheanstalkManagerInterface::class, PheanstalkPublisherInterface::class, PheanstalkSubscriberInterface::class])),
            m::mock(JobIdInterface::class),
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
