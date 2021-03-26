<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\TestCase;

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
        $provider->flush(15);
        $this->assertSame(1, $db->getConnection()->table('failed_jobs')->count());

        $db->getConnection()->table('failed_jobs')->insert(['failed_at' => Date::now()->subDays(10)]);
        $provider->flush(10);
        $this->assertSame(0, $db->getConnection()->table('failed_jobs')->count());
    }
}
