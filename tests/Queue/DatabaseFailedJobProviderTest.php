<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseFailedJobProviderTest extends TestCase
{
    protected $db;

    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createDatabaseWithFailedJobTable()
            ->createProvider();
    }

    public function testCanGetAllFailedJobIds()
    {
        $this->assertEmpty($this->provider->ids());

        array_map(fn () => $this->createFailedJobsRecord(), range(1, 4));

        $this->assertCount(4, $this->provider->ids());
        $this->assertSame([4, 3, 2, 1], $this->provider->ids());
    }

    public function testCanGetAllFailedJobs()
    {
        $this->assertEmpty($this->provider->all());

        array_map(fn () => $this->createFailedJobsRecord(), range(1, 4));

        $this->assertCount(4, $this->provider->all());
        $this->assertSame(3, $this->provider->all()[1]->id);
        $this->assertSame('default', $this->provider->all()[1]->queue);
    }

    public function testCanRetrieveFailedJobsById()
    {
        array_map(fn () => $this->createFailedJobsRecord(), range(1, 2));

        $this->assertNotNull($this->provider->find(1));
        $this->assertNotNull($this->provider->find(2));
        $this->assertNull($this->provider->find(3));
    }

    public function testCanRemoveFailedJobsById()
    {
        $this->createFailedJobsRecord();

        $this->assertFalse($this->provider->forget(2));
        $this->assertSame(1, $this->failedJobsTable()->count());
        $this->assertTrue($this->provider->forget(1));
        $this->assertSame(0, $this->failedJobsTable()->count());
    }

    public function testCanPruneFailedJobs()
    {
        Carbon::setTestNow(Carbon::createFromDate(2024, 4, 28));

        $this->createFailedJobsRecord(['failed_at' => Carbon::createFromDate(2024, 4, 24)]);
        $this->createFailedJobsRecord(['failed_at' => Carbon::createFromDate(2024, 4, 26)]);

        $this->provider->prune(Carbon::createFromDate(2024, 4, 23));
        $this->assertSame(2, $this->failedJobsTable()->count());

        $this->provider->prune(Carbon::createFromDate(2024, 4, 25));
        $this->assertSame(1, $this->failedJobsTable()->count());

        $this->provider->prune(Carbon::createFromDate(2024, 4, 30));
        $this->assertSame(0, $this->failedJobsTable()->count());
    }

    public function testCanPruneFailedJobsWithRelativeHoursAndMinutes()
    {
        Carbon::setTestNow(Carbon::create(2025, 8, 24, 12, 0, 0));

        $this->createFailedJobsRecord(['failed_at' => Carbon::create(2025, 8, 24, 11, 45, 0)]);
        $this->createFailedJobsRecord(['failed_at' => Carbon::create(2025, 8, 24, 13, 0, 0)]);

        $this->provider->prune(Carbon::create(2025, 8, 24, 11, 45, 0));
        $this->assertSame(2, $this->failedJobsTable()->count());

        $this->provider->prune(Carbon::create(2025, 8, 24, 14, 0, 0));
        $this->assertSame(0, $this->failedJobsTable()->count());
    }

    public function testCanFlushFailedJobs()
    {
        Date::setTestNow(Date::now());

        $this->createFailedJobsRecord(['failed_at' => Date::now()->subDays(10)]);
        $this->provider->flush();
        $this->assertSame(0, $this->failedJobsTable()->count());

        $this->createFailedJobsRecord(['failed_at' => Date::now()->subDays(10)]);
        $this->provider->flush(15 * 24);
        $this->assertSame(1, $this->failedJobsTable()->count());

        $this->createFailedJobsRecord(['failed_at' => Date::now()->subDays(10)]);
        $this->provider->flush(10 * 24);
        $this->assertSame(0, $this->failedJobsTable()->count());
    }

    public function testCanProperlyLogFailedJob()
    {
        $uuid = Str::uuid();
        $exception = new Exception(mb_convert_encoding('ÐÑÙ0E\xE2\x�98\xA0World��7B¹!þÿ', 'ISO-8859-1', 'UTF-8'));

        $this->provider->log('database', 'default', json_encode(['uuid' => (string) $uuid]), $exception);

        $exception = (string) mb_convert_encoding($exception, 'UTF-8');

        $this->assertSame(1, $this->failedJobsTable()->count());
        $this->assertSame($exception, $this->failedJobsTable()->first()->exception);
    }

    public function testJobsCanBeCounted()
    {
        $this->assertSame(0, $this->provider->count());

        $this->provider->log('database', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(1, $this->provider->count());

        $this->provider->log('database', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->provider->log('another-connection', 'another-queue', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(3, $this->provider->count());
    }

    public function testJobsCanBeCountedByConnection()
    {
        $this->provider->log('connection-1', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->provider->log('connection-2', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(1, $this->provider->count('connection-1'));
        $this->assertSame(1, $this->provider->count('connection-2'));

        $this->provider->log('connection-1', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(2, $this->provider->count('connection-1'));
        $this->assertSame(1, $this->provider->count('connection-2'));
    }

    public function testJobsCanBeCountedByQueue()
    {
        $this->provider->log('database', 'queue-1', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->provider->log('database', 'queue-2', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(1, $this->provider->count(queue: 'queue-1'));
        $this->assertSame(1, $this->provider->count(queue: 'queue-2'));

        $this->provider->log('database', 'queue-1', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(2, $this->provider->count(queue: 'queue-1'));
        $this->assertSame(1, $this->provider->count(queue: 'queue-2'));
    }

    public function testJobsCanBeCountedByQueueAndConnection()
    {
        $this->provider->log('connection-1', 'queue-99', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->provider->log('connection-1', 'queue-99', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->provider->log('connection-2', 'queue-99', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->provider->log('connection-1', 'queue-1', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->provider->log('connection-2', 'queue-1', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->provider->log('connection-2', 'queue-1', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());

        $this->assertSame(2, $this->provider->count('connection-1', 'queue-99'));
        $this->assertSame(1, $this->provider->count('connection-2', 'queue-99'));
        $this->assertSame(1, $this->provider->count('connection-1', 'queue-1'));
        $this->assertSame(2, $this->provider->count('connection-2', 'queue-1'));
    }

    protected function createSimpleDatabaseWithFailedJobTable()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('failed_at')->useCurrent();
        });

        return $db;
    }

    protected function createDatabaseWithFailedJobTable()
    {
        $this->db = new DB;
        $this->db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        return $this;
    }

    protected function createProvider(string $database = 'default', string $table = 'failed_jobs')
    {
        $this->provider = new DatabaseFailedJobProvider($this->db->getDatabaseManager(), $database, $table);

        return $this;
    }

    protected function failedJobsTable()
    {
        return $this->db->getConnection()->table('failed_jobs');
    }

    protected function createFailedJobsRecord(array $overrides = [])
    {
        return $this->failedJobsTable()
            ->insert(array_merge([
                'connection' => 'database',
                'queue' => 'default',
                'payload' => json_encode(['uuid' => (string) Str::uuid()]),
                'exception' => new Exception('Whoops!'),
                'failed_at' => Date::now()->subDays(10),
            ], $overrides));
    }
}
