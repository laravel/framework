<?php

namespace Illuminate\Tests\Cache;

use Closure;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Database\Connection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class CacheDatabaseStoreTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testNullIsReturnedWhenItemNotFound()
    {
        $store = $this->getStore();
        $table = m::mock(stdClass::class);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('whereIn')->once()->with('key', ['prefixfoo'])->andReturn($table);
        $table->shouldReceive('get')->once()->andReturn(collect([]));

        $this->assertNull($store->get('foo'));
    }

    public function testNullIsReturnedAndItemDeletedWhenItemIsExpired()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['forgetIfExpired'])->setConstructorArgs($this->getMocks())->getMock();

        $getQuery = m::mock(stdClass::class);
        $getQuery->shouldReceive('whereIn')->once()->with('key', ['prefixfoo'])->andReturn($getQuery);
        $getQuery->shouldReceive('get')->once()->andReturn(collect([(object) ['key' => 'prefixfoo', 'expiration' => 1]]));

        $deleteQuery = m::mock(stdClass::class);
        $deleteQuery->shouldReceive('whereIn')->once()->with('key', ['prefixfoo', 'prefixilluminate:cache:flexible:created:foo'])->andReturn($deleteQuery);
        $deleteQuery->shouldReceive('where')->once()->with('expiration', '<=', m::any())->andReturn($deleteQuery);
        $deleteQuery->shouldReceive('delete')->once()->andReturnNull();

        $store->getConnection()->shouldReceive('table')->twice()->with('table')->andReturn($getQuery, $deleteQuery);

        $this->assertNull($store->get('foo'));
    }

    public function testDecryptedValueIsReturnedWhenItemIsValid()
    {
        $store = $this->getStore();
        $table = m::mock(stdClass::class);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('whereIn')->once()->with('key', ['prefixfoo'])->andReturn($table);
        $table->shouldReceive('get')->once()->andReturn(collect([(object) ['key' => 'prefixfoo', 'value' => serialize('bar'), 'expiration' => 999999999999999]]));

        $this->assertSame('bar', $store->get('foo'));
    }

    public function testValueIsReturnedOnPostgres()
    {
        $store = $this->getPostgresStore();
        $table = m::mock(stdClass::class);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('whereIn')->once()->with('key', ['prefixfoo'])->andReturn($table);
        $table->shouldReceive('get')->once()->andReturn(collect([(object) ['key' => 'prefixfoo', 'value' => base64_encode(serialize('bar')), 'expiration' => 999999999999999]]));

        $this->assertSame('bar', $store->get('foo'));
    }

    public function testValueIsReturnedOnSqlite()
    {
        $store = $this->getSqliteStore();
        $table = m::mock(stdClass::class);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('whereIn')->once()->with('key', ['prefixfoo'])->andReturn($table);
        $table->shouldReceive('get')->once()->andReturn(collect([(object) ['key' => 'prefixfoo', 'value' => base64_encode(serialize("\0bar\0")), 'expiration' => 999999999999999]]));

        $this->assertSame("\0bar\0", $store->get('foo'));
    }

    public function testValueIsUpserted()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getMocks())->getMock();
        $table = m::mock(stdClass::class);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $store->expects($this->once())->method('getTime')->willReturn(1);
        $table->shouldReceive('upsert')->once()->with([['key' => 'prefixfoo', 'value' => serialize('bar'), 'expiration' => 61]], 'key')->andReturnTrue();

        $result = $store->put('foo', 'bar', 60);
        $this->assertTrue($result);
    }

    public function testValueIsUpsertedOnPostgres()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getPostgresMocks())->getMock();
        $table = m::mock(stdClass::class);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $store->expects($this->once())->method('getTime')->willReturn(1);
        $table->shouldReceive('upsert')->once()->with([['key' => 'prefixfoo', 'value' => base64_encode(serialize("\0")), 'expiration' => 61]], 'key')->andReturn(1);

        $result = $store->put('foo', "\0", 60);
        $this->assertTrue($result);
    }

    public function testValueIsUpsertedOnSqlite()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['getTime'])->setConstructorArgs($this->getSqliteMocks())->getMock();
        $table = m::mock(stdClass::class);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $store->expects($this->once())->method('getTime')->willReturn(1);
        $table->shouldReceive('upsert')->once()->with([['key' => 'prefixfoo', 'value' => base64_encode(serialize("\0")), 'expiration' => 61]], 'key')->andReturn(1);

        $result = $store->put('foo', "\0", 60);
        $this->assertTrue($result);
    }

    public function testForeverCallsStoreItemWithReallyLongTime()
    {
        $store = $this->getMockBuilder(DatabaseStore::class)->onlyMethods(['put'])->setConstructorArgs($this->getMocks())->getMock();
        $store->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(315360000))->willReturn(true);
        $result = $store->forever('foo', 'bar');
        $this->assertTrue($result);
    }

    public function testItemsMayBeRemovedFromCache()
    {
        $store = $this->getStore();
        $table = m::mock(stdClass::class);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('whereIn')->once()->with('key', ['prefixfoo', 'prefixilluminate:cache:flexible:created:foo'])->andReturn($table);
        $table->shouldReceive('delete')->once();

        $store->forget('foo');
    }

    public function testItemsMayBeFlushedFromCache()
    {
        $store = $this->getStore();
        $table = m::mock(stdClass::class);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('delete')->once()->andReturn(2);

        $result = $store->flush();
        $this->assertTrue($result);
    }

    public function testIncrementReturnsCorrectValues()
    {
        $store = $this->getStore();
        $table = m::mock(stdClass::class);
        $cache = m::mock(stdClass::class);

        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type(Closure::class))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn(null);
        $this->assertFalse($store->increment('foo'));

        $cache->value = serialize('bar');
        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type(Closure::class))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn($cache);
        $this->assertFalse($store->increment('foo'));

        $cache->value = serialize(2);
        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type(Closure::class))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn($cache);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('update')->once()->with(['value' => serialize(3)]);
        $this->assertEquals(3, $store->increment('foo'));
    }

    public function testDecrementReturnsCorrectValues()
    {
        $store = $this->getStore();
        $table = m::mock(stdClass::class);
        $cache = m::mock(stdClass::class);

        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type(Closure::class))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn(null);
        $this->assertFalse($store->decrement('foo'));

        $cache->value = serialize('bar');
        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type(Closure::class))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn($cache);
        $this->assertFalse($store->decrement('foo'));

        $cache->value = serialize(3);
        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type(Closure::class))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixbar')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn($cache);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixbar')->andReturn($table);
        $table->shouldReceive('update')->once()->with(['value' => serialize(2)]);
        $this->assertEquals(2, $store->decrement('bar'));
    }

    protected function getStore()
    {
        return new DatabaseStore(m::mock(Connection::class), 'table', 'prefix');
    }

    protected function getPostgresStore()
    {
        return new DatabaseStore(m::mock(PostgresConnection::class), 'table', 'prefix');
    }

    protected function getSqliteStore()
    {
        return new DatabaseStore(m::mock(SQLiteConnection::class), 'table', 'prefix');
    }

    protected function getMocks()
    {
        return [m::mock(Connection::class), 'table', 'prefix'];
    }

    protected function getPostgresMocks()
    {
        return [m::mock(PostgresConnection::class), 'table', 'prefix'];
    }

    protected function getSqliteMocks()
    {
        return [m::mock(SQLiteConnection::class), 'table', 'prefix'];
    }
}
