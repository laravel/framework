<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Exceptions\JobDispatchedException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Queue;

class UniqueJobDispatchingTest extends QueueTestCase
{
    public function testSubsequentUniqueJobDispatchAreIgnored()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        Queue::fake();

        ThrowableUniqueJob::dispatch();
        ThrowableUniqueJob::dispatch();

        Queue::assertPushed(ThrowableUniqueJob::class);
    }

    public function testSubsequentUniqueJobDispatchCanThrow()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        Queue::fake();
        $this->expectException(JobDispatchedException::class);

        ThrowableUniqueJob::dispatch()->throw();
        ThrowableUniqueJob::dispatch()->throw();

        Queue::assertPushed(ThrowableUniqueJob::class);
    }

    public function testSubsequentUniqueJobDispatchCanThrowWithCallback()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        Queue::fake();
        $this->expectException(JobDispatchedException::class);
        $value = 0;

        ThrowableUniqueJob::dispatch()->throw(fn () => $value = 1);
        ThrowableUniqueJob::dispatch()->throw(fn () => $value = 2);

        Queue::assertPushed(ThrowableUniqueJob::class);
        $this->assertEquals(2, $value);
    }
}

class ThrowableUniqueJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
}
