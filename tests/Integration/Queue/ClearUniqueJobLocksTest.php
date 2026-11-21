<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Orchestra\Testbench\Attributes\WithMigration;
use PHPUnit\Framework\Attributes\DataProvider;
use Predis\Connection\ConnectionException;
use Predis\Connection\Resource\Exception\StreamInitException;

#[WithMigration('queue')]
class ClearUniqueJobLocksTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('cache.stores.unique-test', ['driver' => 'array']);
        $app['config']->set('database.redis.client', 'predis');
    }

    public static function connections(): array
    {
        return [['database'], ['redis']];
    }

    #[DataProvider('connections')]
    public function testClearingReleasesOnlyLocksOwnedByRemovedUnreservedJobs(string $connection): void
    {
        if ($connection === 'redis') {
            try {
                $this->app['redis']->connection()->ping();
            } catch (ConnectionException|StreamInitException) {
                $this->markTestSkipped('Redis is unavailable.');
            }
        }

        $queue = 'clear-test-'.Str::random();
        $otherQueue = $queue.'-other';
        $driver = Queue::connection($connection);
        $store = Cache::store('unique-test');

        try {
            $this->dispatchJob('reserved', $connection, $queue);
            $this->assertNotNull($driver->pop($queue));

            $this->dispatchJob('ready', $connection, $queue);
            $this->dispatchJob('delayed', $connection, $queue, 600);
            $this->dispatchJob('another-queue', $connection, $otherQueue);
            $this->dispatchJob('stale-owner', $connection, $queue);

            $store->lock($this->key('stale-owner'))->forceRelease();
            $this->assertTrue($store->lock($this->key('stale-owner'), 60)->get());
            $this->assertTrue($store->lock('application-lock', 60)->get());

            $this->artisan('queue:clear', ['connection' => $connection, '--queue' => $queue])
                ->expectsOutputToContain('Cleared 4 jobs')
                ->assertSuccessful();

            $this->assertEquals(0, $driver->size($queue));
            $this->assertEquals(1, $driver->size($otherQueue));
            $this->dispatchJob('ready', $connection, $queue);
            $this->assertEquals(1, $driver->size($queue));

            $this->dispatchJob('delayed', $connection, $queue, 600);
            $this->assertEquals(2, $driver->size($queue));

            $this->assertFalse($store->lock($this->key('reserved'))->get());
            $this->dispatchJob('reserved', $connection, $queue);
            $this->assertEquals(2, $driver->size($queue));
            $this->assertFalse($store->lock($this->key('another-queue'))->get());
            $this->assertFalse($store->lock($this->key('stale-owner'))->get());
            $this->assertFalse($store->lock('application-lock')->get());
        } finally {
            $driver->clear($queue);
            $driver->clear($otherQueue);
        }
    }

    public function testDatabaseClearKeepsLocksUntilTheDeleteCommits(): void
    {
        $this->dispatchJob('ready', 'database', 'transaction-test');
        $driver = Queue::connection('database');
        $store = Cache::store('unique-test');

        DB::beginTransaction();
        try {
            $this->assertEquals(1, $driver->clear('transaction-test'));
            $this->assertFalse($store->lock($this->key('ready'))->get());
        } finally {
            DB::rollBack();
        }

        $this->assertEquals(1, $driver->size('transaction-test'));
        $this->assertFalse($store->lock($this->key('ready'))->get());

        DB::transaction(fn () => $driver->clear('transaction-test'));

        $this->assertTrue($store->lock($this->key('ready'))->get());
    }

    private function dispatchJob(string $id, string $connection, string $queue, int $delay = 0): void
    {
        UniqueJobClearedTestJob::dispatch($id)->onConnection($connection)->onQueue($queue)->delay($delay);
    }

    private function key(string $id): string
    {
        return UniqueLock::getKey(new UniqueJobClearedTestJob($id));
    }
}

class UniqueJobClearedTestJob implements ShouldQueue, ShouldBeUnique, ShouldBeEncrypted
{
    use Queueable;

    public function __construct(public string $uniqueId)
    {
    }

    public function uniqueVia()
    {
        return Cache::store('unique-test');
    }
}
