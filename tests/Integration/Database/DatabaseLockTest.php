<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseLockTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    public function testLockCanHaveASeparateConnection()
    {
        $this->app['config']->set('cache.stores.database.lock_connection', 'test');
        $this->app['config']->set('database.connections.test', $this->app['config']->get('database.connections.mysql'));

        $this->assertSame('test', Cache::driver('database')->lock('foo')->getConnectionName());
    }

    public function testLockCanBeAcquired()
    {
        $lock = Cache::driver('database')->lock('foo');
        $this->assertTrue($lock->get());

        $otherLock = Cache::driver('database')->lock('foo');
        $this->assertFalse($otherLock->get());

        $lock->release();

        $otherLock = Cache::driver('database')->lock('foo');
        $this->assertTrue($otherLock->get());

        $otherLock->release();
    }

    public function testLockCanBeForceReleased()
    {
        $lock = Cache::driver('database')->lock('foo');
        $this->assertTrue($lock->get());

        $otherLock = Cache::driver('database')->lock('foo');
        $otherLock->forceRelease();
        $this->assertTrue($otherLock->get());

        $otherLock->release();
    }

    public function testExpiredLockCanBeRetrieved()
    {
        $lock = Cache::driver('database')->lock('foo');
        $this->assertTrue($lock->get());
        DB::table('cache_locks')->update(['expiration' => now()->subDays(1)->getTimestamp()]);

        $otherLock = Cache::driver('database')->lock('foo');
        $this->assertTrue($otherLock->get());

        $otherLock->release();
    }
}
