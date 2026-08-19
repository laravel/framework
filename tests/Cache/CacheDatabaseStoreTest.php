<?php

namespace Illuminate\Tests\Cache;

use Closure;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Database\Connection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\SQLiteConnection;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;

class CacheDatabaseStoreTest extends TestCase
{
    public function testNullIsReturnedWhenItemNotFound()
    {
        $store = $this->getStore();
        $table = Mockery::mock(Builder::class);
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('whereIn')->with('key', ['prefixfoo'])->andReturn($table);
        $table->expects('get')->andReturn(collect([]));

        $this->assertNull($store->get('foo'));
    }

    public function testNullIsReturnedAndItemDeletedWhenItemIsExpired()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['forgetIfExpired'])->setConstructorArgs($this->getMocks())->getMock();

        $getQuery = Mockery::mock(Builder::class);
        $getQuery->expects('whereIn')->with('key', ['prefixfoo'])->andReturn($getQuery);
        $getQuery->expects('get')->andReturn(collect([(object) ['key' => 'prefixfoo', 'expiration' => 1]]));

        $deleteQuery = Mockery::mock(Builder::class);
        $deleteQuery->expects('whereIn')->with('key', ['prefixfoo', 'prefixilluminate:cache:flexible:created:foo'])->andReturn($deleteQuery);
        $deleteQuery->expects('where')->with('expiration', '<=', Mockery::any())->andReturn($deleteQuery);
        $deleteQuery->expects('delete')->andReturnNull();

        $store->getConnection()->expects('table')->times(2)->with('table')->andReturn($getQuery, $deleteQuery);

        $this->assertNull($store->get('foo'));
    }

    public function testDecryptedValueIsReturnedWhenItemIsValid()
    {
        $store = $this->getStore();
        $table = Mockery::mock(Builder::class);
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('whereIn')->with('key', ['prefixfoo'])->andReturn($table);
        $table->expects('get')->andReturn(collect([(object) ['key' => 'prefixfoo', 'value' => serialize('bar'), 'expiration' => 999999999999999]]));

        $this->assertSame('bar', $store->get('foo'));
    }

    public function testValueIsReturnedOnPostgres()
    {
        $store = $this->getPostgresStore();
        $table = Mockery::mock(Builder::class);
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('whereIn')->with('key', ['prefixfoo'])->andReturn($table);
        $table->expects('get')->andReturn(collect([(object) ['key' => 'prefixfoo', 'value' => base64_encode(serialize('bar')), 'expiration' => 999999999999999]]));

        $this->assertSame('bar', $store->get('foo'));
    }

    public function testValueIsReturnedOnSqlite()
    {
        $store = $this->getSqliteStore();
        $table = Mockery::mock(Builder::class);
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('whereIn')->with('key', ['prefixfoo'])->andReturn($table);
        $table->expects('get')->andReturn(collect([(object) ['key' => 'prefixfoo', 'value' => base64_encode(serialize("\0bar\0")), 'expiration' => 999999999999999]]));

        $this->assertSame("\0bar\0", $store->get('foo'));
    }

    public function testValueIsUpserted()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getMocks())->getMock();
        $table = Mockery::mock(Builder::class);
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $store->expects($this->once())->method('getTime')->willReturn(1);
        $table->expects('upsert')->with([['key' => 'prefixfoo', 'value' => serialize('bar'), 'expiration' => 61]], 'key')->andReturnTrue();

        $result = $store->put('foo', 'bar', 60);
        $this->assertTrue($result);
    }

    public function testValueIsUpsertedOnPostgres()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getPostgresMocks())->getMock();
        $table = Mockery::mock(Builder::class);
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $store->expects($this->once())->method('getTime')->willReturn(1);
        $table->expects('upsert')->with([['key' => 'prefixfoo', 'value' => base64_encode(serialize("\0")), 'expiration' => 61]], 'key')->andReturn(1);

        $result = $store->put('foo', "\0", 60);
        $this->assertTrue($result);
    }

    public function testValueIsUpsertedOnSqlite()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getSqliteMocks())->getMock();
        $table = Mockery::mock(Builder::class);
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $store->expects($this->once())->method('getTime')->willReturn(1);
        $table->expects('upsert')->with([['key' => 'prefixfoo', 'value' => base64_encode(serialize("\0")), 'expiration' => 61]], 'key')->andReturn(1);

        $result = $store->put('foo', "\0", 60);
        $this->assertTrue($result);
    }

    public function testForeverCallsStoreItemWithReallyLongTime()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['put'])->setConstructorArgs($this->getMocks())->getMock();
        $store->expects($this->once())->method('put')->with('foo', 'bar', 315360000)->willReturn(true);
        $result = $store->forever('foo', 'bar');
        $this->assertTrue($result);
    }

    public function testItemsMayBeRemovedFromCache()
    {
        $store = $this->getStore();
        $table = Mockery::mock(Builder::class);
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('whereIn')->with('key', ['prefixfoo', 'prefixilluminate:cache:flexible:created:foo'])->andReturn($table);
        $table->expects('delete');

        $store->forget('foo');
    }

    public function testItemsMayBeFlushedFromCache()
    {
        $store = $this->getStore();
        $table = Mockery::mock(Builder::class);
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('delete')->andReturn(2);

        $result = $store->flush();
        $this->assertTrue($result);
    }

    public function testLocksMayBeFlushedFromCache()
    {
        $store = $this->getStore();
        $connection = Mockery::mock(\Illuminate\Database\ConnectionInterface::class);
        $store->setLockConnection($connection);
        $table = Mockery::mock(Builder::class);
        $store->getLockConnection()->expects('table')->with('cache_locks')->andReturn($table);
        $table->expects('delete')->andReturn(2);

        $result = $store->flushLocks();
        $this->assertTrue($result);
    }

    public function testIncrementReturnsCorrectValues()
    {
        $store = $this->getStore();
        $table = Mockery::mock(Builder::class);
        $cache = Mockery::mock(stdClass::class);

        $store->getConnection()->expects('transaction')->with(Mockery::type(Closure::class))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('where')->with('key', 'prefixfoo')->andReturn($table);
        $table->expects('lockForUpdate')->andReturn($table);
        $table->expects('first')->andReturn(null);
        $this->assertFalse($store->increment('foo'));

        $cache->value = serialize('bar');
        $store->getConnection()->expects('transaction')->with(Mockery::type(Closure::class))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('where')->with('key', 'prefixfoo')->andReturn($table);
        $table->expects('lockForUpdate')->andReturn($table);
        $table->expects('first')->andReturn($cache);
        $this->assertFalse($store->increment('foo'));

        $cache->value = serialize(2);
        $store->getConnection()->expects('transaction')->with(Mockery::type(Closure::class))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('where')->with('key', 'prefixfoo')->andReturn($table);
        $table->expects('lockForUpdate')->andReturn($table);
        $table->expects('first')->andReturn($cache);
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('where')->with('key', 'prefixfoo')->andReturn($table);
        $table->expects('update')->with(['value' => serialize(3)]);
        $this->assertEquals(3, $store->increment('foo'));
    }

    public function testDecrementReturnsCorrectValues()
    {
        $store = $this->getStore();
        $table = Mockery::mock(Builder::class);
        $cache = Mockery::mock(stdClass::class);

        $store->getConnection()->expects('transaction')->with(Mockery::type(Closure::class))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('where')->with('key', 'prefixfoo')->andReturn($table);
        $table->expects('lockForUpdate')->andReturn($table);
        $table->expects('first')->andReturn(null);
        $this->assertFalse($store->decrement('foo'));

        $cache->value = serialize('bar');
        $store->getConnection()->expects('transaction')->with(Mockery::type(Closure::class))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('where')->with('key', 'prefixfoo')->andReturn($table);
        $table->expects('lockForUpdate')->andReturn($table);
        $table->expects('first')->andReturn($cache);
        $this->assertFalse($store->decrement('foo'));

        $cache->value = serialize(3);
        $store->getConnection()->expects('transaction')->with(Mockery::type(Closure::class))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('where')->with('key', 'prefixbar')->andReturn($table);
        $table->expects('lockForUpdate')->andReturn($table);
        $table->expects('first')->andReturn($cache);
        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $table->expects('where')->with('key', 'prefixbar')->andReturn($table);
        $table->expects('update')->with(['value' => serialize(2)]);
        $this->assertEquals(2, $store->decrement('bar'));
    }

    public function testTouchExtendsTtl()
    {
        $ttl = 60;

        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getMocks())->getMock();
        $table = Mockery::mock(Builder::class);

        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $store->expects($this->once())->method('getTime')->willReturn(0);
        $table->expects('where')->times(2)->andReturn($table);
        $table->expects('update')->with(['expiration' => $ttl])->andReturn(1);

        $this->assertTrue($store->touch('foo', $ttl));
    }

    public function testTouchExtendsTtlOnPostgres(): void
    {
        $ttl = 60;

        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getPostgresMocks())->getMock();
        $table = Mockery::mock(Builder::class);

        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $store->expects($this->once())->method('getTime')->willReturn(0);
        $table->expects('where')->times(2)->andReturn($table);
        $table->expects('update')->with(['expiration' => $ttl])->andReturn(1);

        $this->assertTrue($store->touch('foo', $ttl));
    }

    public function testTouchExtendsTtlOnSqlite()
    {
        $ttl = 60;

        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getSqliteMocks())->getMock();
        $table = Mockery::mock(Builder::class);

        $store->getConnection()->expects('table')->with('table')->andReturn($table);
        $store->expects($this->once())->method('getTime')->willReturn(0);
        $table->expects('where')->times(2)->andReturn($table);
        $table->expects('update')->with(['expiration' => $ttl])->andReturn(1);

        $this->assertTrue($store->touch('foo', $ttl));
    }

    protected function getStore()
    {
        return new DatabaseStore(Mockery::mock(Connection::class), 'table', 'prefix');
    }

    protected function getPostgresStore()
    {
        return new DatabaseStore(Mockery::mock(PostgresConnection::class), 'table', 'prefix');
    }

    protected function getSqliteStore()
    {
        return new DatabaseStore(Mockery::mock(SQLiteConnection::class), 'table', 'prefix');
    }

    protected function getMocks()
    {
        return [Mockery::mock(Connection::class), 'table', 'prefix'];
    }

    protected function getPostgresMocks()
    {
        return [Mockery::mock(PostgresConnection::class), 'table', 'prefix'];
    }

    protected function getSqliteMocks()
    {
        return [Mockery::mock(SQLiteConnection::class), 'table', 'prefix'];
    }
}
