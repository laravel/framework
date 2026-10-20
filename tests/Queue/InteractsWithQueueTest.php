<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\Job as QueueJob;
use Mockery;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class InteractsWithQueueTest extends TestCase
{
    public function testCreatesAnExceptionFromString()
    {
        $queueJob = Mockery::mock(Job::class);
        $queueJob->expects('fail')->withArgs(function ($e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertSame('Whoops!', $e->getMessage());

            return true;
        });

        $job = new class
        {
            use InteractsWithQueue;

            public $job;
        };

        $job->job = $queueJob;
        $job->fail('Whoops!');
    }

    #[TestWith([1, 10])]
    #[TestWith([2, 60])]
    #[TestWith([3, 300])]
    #[TestWith([5, 300])]
    public function testReleaseUsingBackoff(int $attempts, int $delay)
    {
        $queueJob = Mockery::mock(QueueJob::class);
        $queueJob->allows('backoff')->andReturn('10,60,300');
        $queueJob->allows('attempts')->andReturn($attempts);
        $queueJob->expects('release')->with($delay);

        $job = new class
        {
            use InteractsWithQueue;

            public $job;
        };

        $job->job = $queueJob;
        $job->releaseUsingBackoff();
    }

    public function testReleaseUsingBackoffWithoutBackoff()
    {
        $queueJob = Mockery::mock(QueueJob::class);
        $queueJob->allows('backoff')->andReturn(null);
        $queueJob->allows('attempts')->andReturn(1);
        $queueJob->expects('release')->with(0);

        $job = new class
        {
            use InteractsWithQueue;

            public $job;
        };

        $job->job = $queueJob;
        $job->releaseUsingBackoff();
    }
}
