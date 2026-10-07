<?php

namespace Illuminate\Tests\Cache;

use JMac\Testing\Matching\Argument;
use JMac\Testing\Double;
use Closure;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Database\Connection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\SQLiteConnection;
use Mockery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;

class CacheDatabaseStoreTest extends TestCase
{
    public function testNullIsReturnedWhenItemNotFound()
    {
        $store = $this->getStore();
        $table = Double::for(Builder::class);
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('whereIn')->with('key', ['prefixfoo'])->returns($table);
        $table->expects('get')->returns(collect([]));

        $this->assertNull($store->get('foo'));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testNullIsReturnedAndItemDeletedWhenItemIsExpired()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['forgetIfExpired'])->setConstructorArgs($this->getMocks())->getMock();

        $getQuery = Double::for(Builder::class);
        $getQuery->expects('whereIn')->with('key', ['prefixfoo'])->returns($getQuery);
        $getQuery->expects('get')->returns(collect([(object) ['key' => 'prefixfoo', 'expiration' => 1]]));

        $deleteQuery = Double::for(Builder::class);
        $deleteQuery->expects('whereIn')->with('key', ['prefixfoo', 'prefixilluminate:cache:flexible:created:foo'])->returns($deleteQuery);
        $deleteQuery->expects('where')->with('expiration', '<=', Argument::any())->returns($deleteQuery);
        $deleteQuery->expects('delete')->returns(null);

        $store->getConnection()->expects('table')->times(2)->with('table')->returns($getQuery, $deleteQuery);

        $this->assertNull($store->get('foo'));
    }

    public function testDecryptedValueIsReturnedWhenItemIsValid()
    {
        $store = $this->getStore();
        $table = Double::for(Builder::class);
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('whereIn')->with('key', ['prefixfoo'])->returns($table);
        $table->expects('get')->returns(collect([(object) ['key' => 'prefixfoo', 'value' => serialize('bar'), 'expiration' => 999999999999999]]));

        $this->assertSame('bar', $store->get('foo'));
    }

    public function testValueIsReturnedOnPostgres()
    {
        $store = $this->getPostgresStore();
        $table = Double::for(Builder::class);
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('whereIn')->with('key', ['prefixfoo'])->returns($table);
        $table->expects('get')->returns(collect([(object) ['key' => 'prefixfoo', 'value' => base64_encode(serialize('bar')), 'expiration' => 999999999999999]]));

        $this->assertSame('bar', $store->get('foo'));
    }

    public function testValueIsReturnedOnSqlite()
    {
        $store = $this->getSqliteStore();
        $table = Double::for(Builder::class);
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('whereIn')->with('key', ['prefixfoo'])->returns($table);
        $table->expects('get')->returns(collect([(object) ['key' => 'prefixfoo', 'value' => base64_encode(serialize("\0bar\0")), 'expiration' => 999999999999999]]));

        $this->assertSame("\0bar\0", $store->get('foo'));
    }

    public function testValueIsUpserted()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getMocks())->getMock();
        $table = Double::for(Builder::class);
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $store->expects($this->once())->method('getTime')->willReturn(1);
        $table->expects('upsert')->with([['key' => 'prefixfoo', 'value' => serialize('bar'), 'expiration' => 61]], 'key')->returns(true);

        $result = $store->put('foo', 'bar', 60);
        $this->assertTrue($result);
    }

    public function testValueIsUpsertedOnPostgres()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getPostgresMocks())->getMock();
        $table = Double::for(Builder::class);
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $store->expects($this->once())->method('getTime')->willReturn(1);
        $table->expects('upsert')->with([['key' => 'prefixfoo', 'value' => base64_encode(serialize("\0")), 'expiration' => 61]], 'key')->returns(1);

        $result = $store->put('foo', "\0", 60);
        $this->assertTrue($result);
    }

    public function testValueIsUpsertedOnSqlite()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getSqliteMocks())->getMock();
        $table = Double::for(Builder::class);
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $store->expects($this->once())->method('getTime')->willReturn(1);
        $table->expects('upsert')->with([['key' => 'prefixfoo', 'value' => base64_encode(serialize("\0")), 'expiration' => 61]], 'key')->returns(1);

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
        $table = Double::for(Builder::class);
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('whereIn')->with('key', ['prefixfoo', 'prefixilluminate:cache:flexible:created:foo'])->returns($table);
        $table->expects('delete');

        $store->forget('foo');
    }

    public function testItemsMayBeFlushedFromCache()
    {
        $store = $this->getStore();
        $table = Double::for(Builder::class);
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('delete')->returns(2);

        $result = $store->flush();
        $this->assertTrue($result);
    }

    public function testLocksMayBeFlushedFromCache()
    {
        $store = $this->getStore();
        $connection = Double::for(\Illuminate\Database\ConnectionInterface::class);
        $store->setLockConnection($connection);
        $table = Double::for(Builder::class);
        $store->getLockConnection()->expects('table')->with('cache_locks')->returns($table);
        $table->expects('delete')->returns(2);

        $result = $store->flushLocks();
        $this->assertTrue($result);
    }

    public function testIncrementReturnsCorrectValues()
    {
        $store = $this->getStore();
        $table = Double::for(Builder::class);
        $cache = new stdClass;

        $store->getConnection()->expects('transaction')->with(Argument::type(Closure::class))->resolves(function ($closure) {
            return $closure();
        });
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('where')->with('key', 'prefixfoo')->returns($table);
        $table->expects('lockForUpdate')->returns($table);
        $table->expects('first')->returns(null);
        $this->assertFalse($store->increment('foo'));

        $cache->value = serialize('bar');
        $store->getConnection()->expects('transaction')->with(Argument::type(Closure::class))->resolves(function ($closure) {
            return $closure();
        });
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('where')->with('key', 'prefixfoo')->returns($table);
        $table->expects('lockForUpdate')->returns($table);
        $table->expects('first')->returns($cache);
        $this->assertFalse($store->increment('foo'));

        $cache->value = serialize(2);
        $store->getConnection()->expects('transaction')->with(Argument::type(Closure::class))->resolves(function ($closure) {
            return $closure();
        });
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('where')->with('key', 'prefixfoo')->returns($table);
        $table->expects('lockForUpdate')->returns($table);
        $table->expects('first')->returns($cache);
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('where')->with('key', 'prefixfoo')->returns($table);
        $table->expects('update')->with(['value' => serialize(3)]);
        $this->assertEquals(3, $store->increment('foo'));
    }

    public function testDecrementReturnsCorrectValues()
    {
        $store = $this->getStore();
        $table = Double::for(Builder::class);
        $cache = new stdClass;

        $store->getConnection()->expects('transaction')->with(Argument::type(Closure::class))->resolves(function ($closure) {
            return $closure();
        });
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('where')->with('key', 'prefixfoo')->returns($table);
        $table->expects('lockForUpdate')->returns($table);
        $table->expects('first')->returns(null);
        $this->assertFalse($store->decrement('foo'));

        $cache->value = serialize('bar');
        $store->getConnection()->expects('transaction')->with(Argument::type(Closure::class))->resolves(function ($closure) {
            return $closure();
        });
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('where')->with('key', 'prefixfoo')->returns($table);
        $table->expects('lockForUpdate')->returns($table);
        $table->expects('first')->returns($cache);
        $this->assertFalse($store->decrement('foo'));

        $cache->value = serialize(3);
        $store->getConnection()->expects('transaction')->with(Argument::type(Closure::class))->resolves(function ($closure) {
            return $closure();
        });
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('where')->with('key', 'prefixbar')->returns($table);
        $table->expects('lockForUpdate')->returns($table);
        $table->expects('first')->returns($cache);
        $store->getConnection()->expects('table')->with('table')->returns($table);
        $table->expects('where')->with('key', 'prefixbar')->returns($table);
        $table->expects('update')->with(['value' => serialize(2)]);
        $this->assertEquals(2, $store->decrement('bar'));
    }

    public function testTouchExtendsTtl()
    {
        $ttl = 60;

        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getMocks())->getMock();
        $table = Double::for(Builder::class);

        $store->getConnection()->expects('table')->with('table')->returns($table);
        $store->expects($this->once())->method('getTime')->willReturn(0);
        $table->expects('where')->times(2)->returns($table);
        $table->expects('update')->with(['expiration' => $ttl])->returns(1);

        $this->assertTrue($store->touch('foo', $ttl));
    }

    public function testTouchExtendsTtlOnPostgres(): void
    {
        $ttl = 60;

        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getPostgresMocks())->getMock();
        $table = Double::for(Builder::class);

        $store->getConnection()->expects('table')->with('table')->returns($table);
        $store->expects($this->once())->method('getTime')->willReturn(0);
        $table->expects('where')->times(2)->returns($table);
        $table->expects('update')->with(['expiration' => $ttl])->returns(1);

        $this->assertTrue($store->touch('foo', $ttl));
    }

    public function testTouchExtendsTtlOnSqlite()
    {
        $ttl = 60;

        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getSqliteMocks())->getMock();
        $table = Double::for(Builder::class);

        $store->getConnection()->expects('table')->with('table')->returns($table);
        $store->expects($this->once())->method('getTime')->willReturn(0);
        $table->expects('where')->times(2)->returns($table);
        $table->expects('update')->with(['expiration' => $ttl])->returns(1);

        $this->assertTrue($store->touch('foo', $ttl));
    }

    protected function getStore()
    {
        return new DatabaseStore(Double::for(Connection::class), 'table', 'prefix');
    }

    protected function getPostgresStore()
    {
        return new DatabaseStore(Double::for(PostgresConnection::class), 'table', 'prefix');
    }

    protected function getSqliteStore()
    {
        return new DatabaseStore(Double::for(SQLiteConnection::class), 'table', 'prefix');
    }

    protected function getMocks()
    {
        return [Double::for(Connection::class), 'table', 'prefix'];
    }

    protected function getPostgresMocks()
    {
        return [Double::for(PostgresConnection::class), 'table', 'prefix'];
    }

    protected function getSqliteMocks()
    {
        return [Double::for(SQLiteConnection::class), 'table', 'prefix'];
    }
}
