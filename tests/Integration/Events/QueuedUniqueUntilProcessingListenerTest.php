<?php

namespace Illuminate\Tests\Integration\Events;

use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class QueuedUniqueUntilProcessingListenerTest extends TestCase
{
    use DatabaseMigrations;

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);
        $app['config']->set('queue.default', 'database');
        $app['config']->set('cache.default', 'database');
    }


    public function testUniqueListenersPushedToQueue()
    {
        Event::listen(UniqueUntilProcessingListenerTestEvent::class, UniqueUntilProcessingListener::class);

        UniqueUntilProcessingListenerTestEvent::dispatch();
        UniqueUntilProcessingListenerTestEvent::dispatch();

        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('cache_locks', 1);

        $this->artisan('queue:work', [
            '--memory' => 1024,
            '--once' => true,
        ])->assertSuccessful();

        $this->assertEquals(UniqueUntilProcessingListener::$lockedCountsInProcessing, 0);

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('cache_locks', 0);
    }
}

class UniqueUntilProcessingListenerTestEvent
{
    use Dispatchable;
}

class UniqueUntilProcessingListener implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    public static $lockedCountsInProcessing = null;

    public function handle()
    {
        static::$lockedCountsInProcessing = DB::table('cache_locks')->count();
    }
}
