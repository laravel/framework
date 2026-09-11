<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Attributes\WithMigration;
use RuntimeException;

use function Illuminate\Support\defer;

#[WithMigration]
#[WithMigration('queue')]
class SyncQueueFailedJobsTest extends QueueTestCase
{
    use DatabaseMigrations;

    public function test_failed_sync_jobs_are_recorded()
    {
        SyncQueueFailedJob::$attempts = 0;

        try {
            SyncQueueFailedJob::dispatch()->onConnection('sync');
        } catch (RuntimeException) {
            //
        }

        $this->assertSame(1, DB::table('failed_jobs')->count());
        $this->assertSame('sync', DB::table('failed_jobs')->value('connection'));
    }

    public function test_failed_deferred_jobs_are_recorded()
    {
        SyncQueueFailedJob::$attempts = 0;

        SyncQueueFailedJob::dispatch()->onConnection('deferred');

        defer()->invoke();

        $this->assertSame(1, SyncQueueFailedJob::$attempts);
        $this->assertSame(1, DB::table('failed_jobs')->count());
        $this->assertSame('deferred', DB::table('failed_jobs')->value('connection'));
    }

    public function test_failed_sync_jobs_can_be_retried()
    {
        SyncQueueFailedJob::$attempts = 0;

        try {
            SyncQueueFailedJob::dispatch()->onConnection('sync');
        } catch (RuntimeException) {
            //
        }

        $this->assertSame(1, DB::table('failed_jobs')->count());

        $this->artisan('queue:retry', ['id' => ['all']])->assertSuccessful();

        $this->assertSame(2, SyncQueueFailedJob::$attempts);
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }
}

class SyncQueueFailedJob implements ShouldQueue
{
    use Queueable;

    public static int $attempts = 0;

    public $tries = 1;

    public function handle()
    {
        if (++static::$attempts === 1) {
            throw new RuntimeException('Job failed.');
        }
    }
}
