<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Jobs\BeanstalkdJob;
use Illuminate\Queue\Jobs\Job;
use JMac\Testing\Double;
use JMac\Testing\Matching\Argument;
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
        $job->getPheanstalkJob()->expects('getData')->returns(json_encode(['job' => 'foo', 'data' => ['data']]));
        $handler = Double::for(stdClass::class);
        $job->getContainer()->expects('make')->with('foo')->returns($handler);
        $handler->expects('fire')->with($job, ['data']);

        $job->fire();
    }

    public function testFailProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getPheanstalkJob()->expects('getData')->returns(json_encode(['job' => 'foo', 'uuid' => 'test-uuid', 'data' => ['data']]));
        $handler = Double::for(BeanstalkdJobTestFailedTest::class);
        $job->getContainer()->expects('make')->with('foo')->returns($handler);
        $job->getPheanstalk()->expects('delete')->with($job->getPheanstalkJob())->returns($job->getPheanstalk());
        $handler->expects('failed')->with(['data'], Argument::type(Exception::class), 'test-uuid', Argument::type(Job::class));
        $events = new EventsDispatcher;
        $failed = [];
        $events->listen(JobFailed::class, function ($event) use (&$failed) {
            $failed[] = $event;
        });
        $job->getContainer()->expects('make')->with(Dispatcher::class)->returns($events);

        $job->fail($exception = new Exception);

        $this->assertCount(1, $failed);
        $this->assertSame($job, $failed[0]->job);
        $this->assertSame($exception, $failed[0]->exception);
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
            Double::for(Container::class),
            Double::for(implode(',', [PheanstalkManagerInterface::class, PheanstalkPublisherInterface::class, PheanstalkSubscriberInterface::class])),
            Double::for(JobIdInterface::class),
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
