<?php

namespace Illuminate\Tests\Integration\Database\Queue;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration('laravel', 'queue')]
#[WithConfig('queue.default', 'database')]
class DatabaseQueueBulkTest extends DatabaseTestCase
{
    public function testBulkPushesMoreJobsThanTheDatabaseAllowsPlaceholdersInOneQuery()
    {
        Queue::bulk(array_fill(0, 11000, 'foo'), ['data']);

        $this->assertSame(11000, DB::table('jobs')->count());
    }
}
