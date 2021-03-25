<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class FailedJobTest extends TestCase
{
    public function testEmptyFailingJobStillEmitsEvent()
    {
        Event::fake();

        FailingJob::dispatchNow($this->app);

        Event::assertDispatched(JobFailed::class, function (JobFailed $event) {
            return 'foo' === $event->job->getJobId();
        });
    }
}

class FailingJob extends Job
{
    use Dispatchable;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function handle()
    {
        $this->fail();
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return 'foo';
    }

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return '';
    }
}
