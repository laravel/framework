<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Cache\DatabaseLock;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery as m;
use Orchestra\Testbench\Attributes\WithMigration;
use PDOException;
use PHPUnit\Framework\Attributes\TestWith;

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

    #[TestWith(['Deadlock found when trying to get lock', 1213, true])]
    #[TestWith(['Table does not exist', 1146, false])]
    public function testIgnoresConcurrencyException(string $message, int $code, bool $hasConcurrenyError)
    {
        $connection = m::mock(Connection::class);
        $insertBuilder = m::mock(Builder::class);
        $deleteBuilder = m::mock(Builder::class);

        $insertBuilder->shouldReceive('insert')->once()->andReturn(true);

        $deleteBuilder->shouldReceive('where')->with('expiration', '<=', m::any())->once()->andReturnSelf();
        $deleteBuilder->shouldReceive('delete')->once()->andThrow(
            new QueryException(
                'mysql',
                'delete from cache_locks where expiration <= ?',
                [],
                new PDOException($message, $code)
            )
        );

        $connection->shouldReceive('table')->with('cache_locks')->andReturn($insertBuilder, $deleteBuilder);

        $lock = new DatabaseLock($connection, 'cache_locks', 'foo', 0, lottery: [1, 1]);

        if ($hasConcurrenyError) {
            $this->assertTrue($lock->acquire());
        } else {
            $this->expectException(QueryException::class);
            $this->assertFalse($lock->acquire());
        }
    }
}
