<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseUuidFailedJobProviderTest extends TestCase
{
    public function testGetIdsOfAllFailedJobs()
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
