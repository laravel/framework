<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseUuidFailedJobProviderTest extends TestCase
{
    public function testGettingIdsOfAllFailedJobs()
    {
        $provider = $this->getFailedJobProvider();

        $provider->log('connection-1', 'queue-1', json_encode(['uuid' => 'uuid-1']), new RuntimeException());
        $provider->log('connection-1', 'queue-1', json_encode(['uuid' => 'uuid-2']), new RuntimeException());
        $provider->log('connection-2', 'queue-2', json_encode(['uuid' => 'uuid-3']), new RuntimeException());
        $provider->log('connection-2', 'queue-2', json_encode(['uuid' => 'uuid-4']), new RuntimeException());

        $this->assertSame(['uuid-1', 'uuid-2', 'uuid-3', 'uuid-4'], $provider->ids());
        $this->assertSame(['uuid-1', 'uuid-2'], $provider->ids('queue-1'));
        $this->assertSame(['uuid-3', 'uuid-4'], $provider->ids('queue-2'));
    }

    public function testGettingAllFailedJobs()
    {
        $provider = $this->getFailedJobProvider();

        $this->assertEmpty($provider->all());

        $provider->log('connection-1', 'queue-1', json_encode(['uuid' => 'uuid-1']), new RuntimeException());
        $provider->log('connection-1', 'queue-1', json_encode(['uuid' => 'uuid-2']), new RuntimeException());
        $provider->log('connection-2', 'queue-2', json_encode(['uuid' => 'uuid-3']), new RuntimeException());
        $provider->log('connection-2', 'queue-2', json_encode(['uuid' => 'uuid-4']), new RuntimeException());

        $this->assertCount(4, $provider->all());

        $this->assertSame(
            ['uuid-1', 'uuid-2', 'uuid-3', 'uuid-4'],
            array_column($provider->all(), 'id')
        );
    }

    public function testFindingFailedJobsById()
    {
        $provider = $this->getFailedJobProvider();

        $provider->log('connection-1', 'queue-1', json_encode(['uuid' => 'uuid-1']), new RuntimeException());

        $this->assertNull($provider->find('uuid-2'));
        $this->assertEquals('uuid-1', $provider->find('uuid-1')->id);
        $this->assertEquals('queue-1', $provider->find('uuid-1')->queue);
        $this->assertEquals('connection-1', $provider->find('uuid-1')->connection);
    }

    public function testRemovingJobsById()
    {
        $provider = $this->getFailedJobProvider();

        $provider->log('connection-1', 'queue-1', json_encode(['uuid' => 'uuid-1']), new RuntimeException());

        $this->assertNotNull($provider->find('uuid-1'));

        $provider->forget('uuid-1');

        $this->assertNull($provider->find('uuid-1'));
    }

    public function testRemovingAllFailedJobs()
    {
        $provider = $this->getFailedJobProvider();

        $provider->log('connection-1', 'queue-1', json_encode(['uuid' => 'uuid-1']), new RuntimeException());
        $provider->log('connection-2', 'queue-2', json_encode(['uuid' => 'uuid-2']), new RuntimeException());

        $this->assertCount(2, $provider->all());

        $provider->flush();

        $this->assertEmpty($provider->all());
    }

    public function testPruningFailedJobs()
    {
        $provider = $this->getFailedJobProvider();

        Carbon::setTestNow(Carbon::createFromDate(2024, 4, 28));

        $provider->log('connection-1', 'queue-1', json_encode(['uuid' => 'uuid-1']), new RuntimeException());
        $provider->log('connection-2', 'queue-2', json_encode(['uuid' => 'uuid-2']), new RuntimeException());

        $provider->prune(Carbon::createFromDate(2024, 4, 26));

        $this->assertCount(2, $provider->all());

        $provider->prune(Carbon::createFromDate(2024, 4, 30));

        $this->assertEmpty($provider->all());
    }

    public function testPruningFailedJobsWithRelativeHoursAndMinutes()
    {
        $provider = $this->getFailedJobProvider();

        Carbon::setTestNow(Carbon::create(2025, 8, 24, 12, 30, 0));

        $provider->log('connection-1', 'queue-1', json_encode(['uuid' => 'uuid-1']), new RuntimeException());
        $provider->log('connection-2', 'queue-2', json_encode(['uuid' => 'uuid-2']), new RuntimeException());

        $provider->prune(Carbon::create(2025, 8, 24, 12, 30, 0));

        $this->assertCount(2, $provider->all());

        $provider->prune(Carbon::create(2025, 8, 24, 13, 0, 0));

        $this->assertEmpty($provider->all());
    }

    public function testJobsCanBeCounted()
    {
        $provider = $this->getFailedJobProvider();

        $this->assertSame(0, $provider->count());

        $provider->log('connection-1', 'queue-1', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(1, $provider->count());

        $provider->log('connection-1', 'queue-1', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $provider->log('connection-2', 'queue-2', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(3, $provider->count());
    }

    public function testJobsCanBeCountedByConnection()
    {
        $provider = $this->getFailedJobProvider();

        $provider->log('connection-1', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $provider->log('connection-2', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(1, $provider->count('connection-1'));
        $this->assertSame(1, $provider->count('connection-2'));

        $provider->log('connection-1', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(2, $provider->count('connection-1'));
        $this->assertSame(1, $provider->count('connection-2'));
    }

    public function testJobsCanBeCountedByQueue()
    {
        $provider = $this->getFailedJobProvider();

        $provider->log('database', 'queue-1', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $provider->log('database', 'queue-2', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(1, $provider->count(queue: 'queue-1'));
        $this->assertSame(1, $provider->count(queue: 'queue-2'));

        $provider->log('database', 'queue-1', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(2, $provider->count(queue: 'queue-1'));
        $this->assertSame(1, $provider->count(queue: 'queue-2'));
    }

    public function testJobsCanBeCountedByQueueAndConnection()
    {
        $provider = $this->getFailedJobProvider();

        $provider->log('connection-1', 'queue-99', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $provider->log('connection-1', 'queue-99', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $provider->log('connection-2', 'queue-99', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $provider->log('connection-1', 'queue-1', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $provider->log('connection-2', 'queue-1', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $provider->log('connection-2', 'queue-1', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(2, $provider->count('connection-1', 'queue-99'));
        $this->assertSame(1, $provider->count('connection-2', 'queue-99'));
        $this->assertSame(1, $provider->count('connection-1', 'queue-1'));
        $this->assertSame(2, $provider->count('connection-2', 'queue-1'));
    }

    protected function getFailedJobProvider(string $database = 'default', string $table = 'failed_jobs')
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->uuid();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        return new DatabaseUuidFailedJobProvider($db->getDatabaseManager(), $database, $table);
    }
}
