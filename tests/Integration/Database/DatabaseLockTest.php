<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration('cache')]
class DatabaseLockTest extends DatabaseTestCase
{
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

    public function testOtherOwnerDoesNotOwnLockAfterRestore()
    {
        $firstLock = Cache::store('database')->lock('foo');
        $this->assertTrue($firstLock->isOwnedBy(null));
        $this->assertTrue($firstLock->get());
        $this->assertTrue($firstLock->isOwnedBy($firstLock->owner()));

        $secondLock = Cache::store('database')->restoreLock('foo', 'other_owner');
        $this->assertTrue($secondLock->isOwnedBy($firstLock->owner()));
        $this->assertFalse($secondLock->isOwnedByCurrentProcess());
    }
}
