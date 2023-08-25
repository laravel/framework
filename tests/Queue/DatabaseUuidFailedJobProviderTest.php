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
    public function testJobsCanBeCounted()
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
        $provider = new DatabaseUuidFailedJobProvider($db->getDatabaseManager(), 'default', 'failed_jobs');

        $this->assertCount(0, $provider);

        $provider->log('database', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertCount(1, $provider);

        $provider->log('database', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $provider->log('database', 'default', json_encode(['uuid' => (string) Str::uuid()]), new RuntimeException());
        $this->assertCount(3, $provider);
    }
}
