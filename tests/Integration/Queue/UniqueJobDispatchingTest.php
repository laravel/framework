<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Foundation\Bus\Exceptions\JobDispatchedException;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class UniqueJobDispatchingTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('cache.default', 'database');
    }

    public function testSubsequentUniqueJobDispatchAreIgnored()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        Queue::fake();

        Fixtures\UniqueJob::dispatch();
        Fixtures\UniqueJob::dispatch();

        Queue::assertPushed(Fixtures\UniqueJob::class);
    }

    public function testFirstUniqueJobDispatchDoesNotThrow()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        Queue::fake();

        Fixtures\UniqueJob::dispatch()->throw();

        Queue::assertPushed(Fixtures\UniqueJob::class);
    }

    public function testSubsequentUniqueJobDispatchCanThrowWithCallback()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        Queue::fake();
        $this->expectException(JobDispatchedException::class);
        $value = 0;

        Fixtures\UniqueJob::dispatch()->throw(fn () => $value = 1);
        Fixtures\UniqueJob::dispatch()->throw(fn () => $value = 2);

        Queue::assertPushed(Fixtures\UniqueJob::class);
        $this->assertEquals(2, $value);
    }
}
