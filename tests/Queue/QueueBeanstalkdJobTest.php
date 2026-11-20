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
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\Job as PheanstalkJob;
use Pheanstalk\Values\JobId;
use PHPUnit\Framework\TestCase;

class QueueBeanstalkdJobTest extends TestCase
{
    use VerifiesDoubles;

    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob(json_encode(['job' => 'foo', 'data' => ['data']]));
        $handler = new BeanstalkdJobTestHandler;
        $job->getContainer()->instance('foo', $handler);

        $job->fire();

        $this->assertSame([[$job, ['data']]], $handler->fired);
    }

    public function testFailProperlyCallsTheJobHandler()
    {
        $job = $this->getJob(json_encode(['job' => 'foo', 'uuid' => 'test-uuid', 'data' => ['data']]));
        $handler = new BeanstalkdJobTestFailedTest;
        $job->getContainer()->instance('foo', $handler);
        $job->getPheanstalk()->expects('delete')->with($job->getPheanstalkJob())->returns($job->getPheanstalk());
        $events = new EventsDispatcher;
        $failed = [];
        $events->listen(JobFailed::class, function ($event) use (&$failed) {
            $failed[] = $event;
        });
        $job->getContainer()->instance(Dispatcher::class, $events);

        $job->fail($exception = new Exception);

        $this->assertCount(1, $handler->failed);
        $this->assertSame(['data'], $handler->failed[0][0]);
        $this->assertSame($exception, $handler->failed[0][1]);
        $this->assertSame('test-uuid', $handler->failed[0][2]);
        $this->assertInstanceOf(Job::class, $handler->failed[0][3]);

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
    public array $fired = [];

    public function fire($job, array $data)
    {
        $this->fired[] = [$job, $data];
    }
}

class BeanstalkdJobTestFailedTest
{
    public array $failed = [];

    public function failed(array $data, $exception = null, $uuid = null, $job = null)
    {
        $this->failed[] = [$data, $exception, $uuid, $job];
    }
}
