<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\AfterCommit;
use Illuminate\Queue\Attributes\BeforeCommit;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Queue;
use PHPUnit\Framework\TestCase;

class BeforeCommitContractTest extends TestCase
{
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

    public function testJobWithoutContractRespectsAfterCommitAttribute()
    {
        $job = new JobWithAfterCommitAttribute;

        $this->assertTrue($this->shouldDispatchAfterCommit($job));
    }

    public function testJobWithoutContractRespectsBeforeCommitAttribute()
    {
        $job = new JobWithBeforeCommitAttribute;

        $this->assertFalse($this->shouldDispatchAfterCommit($job));
    }

    public function testRuntimeBeforeCommitOverridesAfterCommitAttribute()
    {
        $job = (new JobWithAfterCommitAttribute)->beforeCommit();

        $this->assertFalse($this->shouldDispatchAfterCommit($job));
    }

    public function testRuntimeAfterCommitOverridesBeforeCommitAttribute()
    {
        $job = (new JobWithBeforeCommitAttribute)->afterCommit();

        $this->assertTrue($this->shouldDispatchAfterCommit($job));
    }

    public function testBeforeCommitAttributeOverridesAfterCommitContract()
    {
        $job = new JobWithBeforeCommitAttributeAndAfterCommitContract;

        $this->assertFalse($this->shouldDispatchAfterCommit($job));
    }

    protected function shouldDispatchAfterCommit($job)
    {
        return (new class extends Queue
        {
            public function shouldDispatchAfterCommit($job)
            {
                return parent::shouldDispatchAfterCommit($job);
            }
        })->shouldDispatchAfterCommit($job);
    }
}

#[AfterCommit]
class JobWithAfterCommitAttribute
{
    use Dispatchable, InteractsWithQueue, Queueable;
}

#[BeforeCommit]
class JobWithBeforeCommitAttribute
{
    use Dispatchable, InteractsWithQueue, Queueable;
}

#[BeforeCommit]
class JobWithBeforeCommitAttributeAndAfterCommitContract implements ShouldQueueAfterCommit
{
    use Dispatchable, InteractsWithQueue, Queueable;
}
