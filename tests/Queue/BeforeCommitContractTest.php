<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BeforeCommitContractTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testJobWithoutContractRespectsBeforeCommit()
    {
        $job = new class
        {
            use Dispatchable, InteractsWithQueue, Queueable;

            public function beforeCommit()
            {
                $this->afterCommit = false;

                return $this;
            }
        };

        $this->assertFalse($this->shouldDispatchAfterCommit($job));
    }

    public function testJobWithoutContractRespectsAfterCommit()
    {
        $job = new class
        {
            use Dispatchable, InteractsWithQueue, Queueable;

            public function afterCommit()
            {
                $this->afterCommit = true;

                return $this;
            }
        };

        $job->afterCommit();

        $this->assertTrue($this->shouldDispatchAfterCommit($job));
    }

    public function testJobWithContractDefaultsToAfterCommit()
    {
        $job = new class implements ShouldQueueAfterCommit
        {
            use Dispatchable, InteractsWithQueue, Queueable;
        };

        $this->assertTrue($this->shouldDispatchAfterCommit($job));
    }

    public function testJobWithContractAndAfterCommitFalseRespectsBeforeCommit()
    {
        $job = new class implements ShouldQueueAfterCommit
        {
            use Dispatchable, InteractsWithQueue, Queueable;

            public function beforeCommit()
            {
                $this->afterCommit = false;

                return $this;
            }
        };

        $job->beforeCommit();

        $this->assertFalse($this->shouldDispatchAfterCommit($job));
    }

    public function testJobWithContractAndExplicitAfterCommitTrueStillSchedulesAfterCommit()
    {
        $job = new class implements ShouldQueueAfterCommit
        {
            use Dispatchable, InteractsWithQueue, Queueable;

            public function afterCommit()
            {
                $this->afterCommit = true;

                return $this;
            }
        };

        $job->afterCommit();

        $this->assertTrue($this->shouldDispatchAfterCommit($job));
    }

    protected function shouldDispatchAfterCommit($job)
    {
        if ($job instanceof ShouldQueueAfterCommit) {
            return ! (isset($job->afterCommit) && $job->afterCommit === false);
        }

        return isset($job->afterCommit) ? $job->afterCommit : false;
    }
}
