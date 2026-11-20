<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\FakeJob;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\TestCase;

class InteractsWithQueueTest extends TestCase
{
    use VerifiesDoubles;

    public function testCreatesAnExceptionFromString()
    {
        $queueJob = Double::for(Job::class);
        $queueJob->expects('fail')->with(Argument::satisfies(function ($e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertSame('Whoops!', $e->getMessage());

            return true;
        }));

        $job = new class
        {
            use InteractsWithQueue;

            public $job;
        };

        $job->job = $queueJob;
        $job->fail('Whoops!');
    }

    public function testReleaseUsingBackoffUsesTheDelayForTheCurrentAttempt()
    {
        $job = $this->jobWithBackoff('10,30,60', attempts: 2);

        $job->releaseUsingBackoff();

        $this->assertSame(30, $job->job->releaseDelay);
    }

    public function testReleaseUsingBackoffUsesTheLastDelayWhenAttemptsExceedTheSchedule()
    {
        $job = $this->jobWithBackoff('10,30,60', attempts: 5);

        $job->releaseUsingBackoff();

        $this->assertSame(60, $job->job->releaseDelay);
    }

    public function testReleaseUsingBackoffSupportsASingleValue()
    {
        $job = $this->jobWithBackoff(15, attempts: 3);

        $job->releaseUsingBackoff();

        $this->assertSame(15, $job->job->releaseDelay);
    }

    public function testReleaseUsingBackoffReleasesImmediatelyWithoutABackoff()
    {
        $job = $this->jobWithBackoff(null, attempts: 1);

        $job->releaseUsingBackoff();

        $this->assertTrue($job->job->isReleased());
        $this->assertSame(0, $job->job->releaseDelay);
    }

    protected function jobWithBackoff($backoff, int $attempts)
    {
        $job = new class
        {
            use InteractsWithQueue;

            public $job;
        };

        $job->job = new class($backoff, $attempts) extends FakeJob
        {
            public function __construct(protected $backoff, protected int $attemptCount)
            {
                //
            }

            public function backoff()
            {
                return $this->backoff;
            }

            public function attempts()
            {
                return $this->attemptCount;
            }
        };

        return $job;
    }
}
