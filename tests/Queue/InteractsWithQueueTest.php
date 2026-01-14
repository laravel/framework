<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\InteractsWithQueue;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class InteractsWithQueueTest extends TestCase
{
    public function testCreatesAnExceptionFromString()
    {
        $queueJob = m::mock(Job::class);
        $queueJob->shouldReceive('fail')->once()->withArgs(function ($e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals('Whoops!', $e->getMessage());

            return true;
        });

        $job = new class
        {
            use InteractsWithQueue;

            public $job;
        };

        $job->job = $queueJob;
        $job->fail('Whoops!');

        $queueJob->mockery_verify();
    }

    public function testFailsJobWhenConditionIsTrue()
    {
        $queueJob = m::mock(Job::class);
        $queueJob->shouldReceive('fail')->once()->withArgs(function ($e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals('Whoops!', $e->getMessage());

            return true;
        });

        $job = new class
        {
            use InteractsWithQueue;

            public $job;
        };

        $job->job = $queueJob;
        $job->failIf(true, 'Whoops!');

        $queueJob->mockery_verify();
    }

    public function testDoesntCreateAnExceptionWhenBooleanIsFalse()
    {
        $queueJob = m::mock(Job::class);
        $queueJob->shouldNotReceive('fail');

        $job = new class
        {
            use InteractsWithQueue;

            public $job;
        };

        $job->job = $queueJob;
        $job->failIf(false, 'Whoops!');

        $queueJob->mockery_verify();
    }
}
