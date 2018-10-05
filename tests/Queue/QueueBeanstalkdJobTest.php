<?php

namespace Illuminate\Tests\Queue;

use stdClass;
use Exception;
use Mockery as m;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\BeanstalkdJob;

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
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock(stdClass::class));
        $handler->shouldReceive('fire')->once()->with($job, ['data']);

        $job->fire();
    }

    public function testFailedProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getPheanstalkJob()->shouldReceive('getData')->once()->andReturn(json_encode(['job' => 'foo', 'data' => ['data']]));
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock(BeanstalkdJobTestFailedTest::class));
        $handler->shouldReceive('failed')->once()->with(['data'], m::type(Exception::class));

        $job->failed(new Exception);
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
