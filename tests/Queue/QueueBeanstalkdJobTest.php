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
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\Job as PheanstalkJob;
use Pheanstalk\Values\JobId;
use PHPUnit\Framework\TestCase;

class QueueBeanstalkdJobTest extends TestCase
{
    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob(json_encode(['job' => 'foo', 'data' => ['data']]));
        $handler = Double::for(BeanstalkdJobTestHandler::class);
        $job->getContainer()->instance('foo', $handler);
        $handler->expects('fire')->with($job, ['data']);

        $job->fire();
    }

    public function testFailProperlyCallsTheJobHandler()
    {
        $job = $this->getJob(json_encode(['job' => 'foo', 'uuid' => 'test-uuid', 'data' => ['data']]));
        $handler = Double::for(BeanstalkdJobTestFailedTest::class);
        $job->getContainer()->instance('foo', $handler);
        $job->getPheanstalk()->expects('delete')->with($job->getPheanstalkJob())->returns($job->getPheanstalk());
        $handler->expects('failed')->with(['data'], Argument::type(Exception::class), 'test-uuid', Argument::type(Job::class));
        $events = new EventsDispatcher;
        $failed = [];
        $events->listen(JobFailed::class, function ($event) use (&$failed) {
            $failed[] = $event;
        });
        $job->getContainer()->instance(Dispatcher::class, $events);

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

    protected function getJob(string $data = '')
    {
        return new BeanstalkdJob(
            new Container,
            Double::for(PheanstalkManagerInterface::class, PheanstalkPublisherInterface::class, PheanstalkSubscriberInterface::class),
            new PheanstalkJob(new JobId(1), $data),
            'connection-name',
            'default'
        );
    }
}

class BeanstalkdJobTestHandler
{
    public function fire($job, array $data)
    {
        //
    }
}

class BeanstalkdJobTestFailedTest
{
    public function failed(array $data)
    {
        //
    }
}
