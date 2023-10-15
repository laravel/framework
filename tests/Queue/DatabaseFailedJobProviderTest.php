<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseFailedJobProviderTest extends TestCase
{
    public function testCanFlushFailedJobs()
    {
        Date::setTestNow(Date::now());

        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('failed_at')->useCurrent();
        });

        $provider = new DatabaseFailedJobProvider($db->getDatabaseManager(), 'default', 'failed_jobs');

        $db->getConnection()->table('failed_jobs')->insert(['failed_at' => Date::now()->subDays(10)]);
        $provider->flush();
        $this->assertSame(0, $db->getConnection()->table('failed_jobs')->count());

        $db->getConnection()->table('failed_jobs')->insert(['failed_at' => Date::now()->subDays(10)]);
        $provider->flush(15 * 24);
        $this->assertSame(1, $db->getConnection()->table('failed_jobs')->count());

        $db->getConnection()->table('failed_jobs')->insert(['failed_at' => Date::now()->subDays(10)]);
        $provider->flush(10 * 24);
        $this->assertSame(0, $db->getConnection()->table('failed_jobs')->count());
    }

    public function testCanProperlyLogFailedJob()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        $uuid = Str::uuid();

        $exception = new Exception(mb_convert_encoding('ÐÑÙ0E\xE2\x�98\xA0World��7B¹!þÿ', 'ISO-8859-1', 'UTF-8'));
        $provider = new DatabaseFailedJobProvider($db->getDatabaseManager(), 'default', 'failed_jobs');

        $provider->log('database', 'default', json_encode(['uuid' => (string) $uuid]), $exception);

        $exception = (string) mb_convert_encoding($exception, 'UTF-8');

        $this->assertSame(1, $db->getConnection()->table('failed_jobs')->count());
        $this->assertSame($exception, $db->getConnection()->table('failed_jobs')->first()->exception);
    }

    public function testJobsCanBeCounted()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
        $provider = new DatabaseFailedJobProvider($db->getDatabaseManager(), 'default', 'failed_jobs');

        $this->assertSame(0, $provider->count());

        $provider->log('database', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(1, $provider->count());

        $provider->log('database', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $provider->log('another-connection', 'another-queue', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertSame(3, $provider->count());
    }

    public function testJobsCanBeCountedByConnection()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
        $provider = new DatabaseFailedJobProvider($db->getDatabaseManager(), 'default', 'failed_jobs');

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
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
        $provider = new DatabaseFailedJobProvider($db->getDatabaseManager(), 'default', 'failed_jobs');

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
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
        $provider = new DatabaseFailedJobProvider($db->getDatabaseManager(), 'default', 'failed_jobs');

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
}
