<?php

namespace Illuminate\Tests\Cache;

use Exception;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\DatabaseStore;

class CacheDatabaseStoreTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testNullIsReturnedWhenItemNotFound(): void
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn(null);

        $this->assertNull($store->get('foo'));
    }

    public function testNullIsReturnedAndItemDeletedWhenItemIsExpired(): void
    {
        $store = $this->getMockBuilder('Illuminate\Cache\DatabaseStore')->setMethods(['forget'])->setConstructorArgs($this->getMocks())->getMock();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) ['expiration' => 1]);
        $store->expects($this->once())->method('forget')->with($this->equalTo('foo'))->will($this->returnValue(null));

        $this->assertNull($store->get('foo'));
    }

    public function testDecryptedValueIsReturnedWhenItemIsValid(): void
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) ['value' => serialize('bar'), 'expiration' => 999999999999999]);

        $this->assertEquals('bar', $store->get('foo'));
    }

    public function testValueIsInsertedWhenNoExceptionsAreThrown(): void
    {
        $store = $this->getMockBuilder('Illuminate\Cache\DatabaseStore')->setMethods(['getTime'])->setConstructorArgs($this->getMocks())->getMock();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $store->expects($this->once())->method('getTime')->will($this->returnValue(1));
        $table->shouldReceive('insert')->once()->with(['key' => 'prefixfoo', 'value' => serialize('bar'), 'expiration' => 61]);

        $store->put('foo', 'bar', 1);
    }

    public function testValueIsUpdatedWhenInsertThrowsException(): void
    {
        $store = $this->getMockBuilder('Illuminate\Cache\DatabaseStore')->setMethods(['getTime'])->setConstructorArgs($this->getMocks())->getMock();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->with('table')->andReturn($table);
        $store->expects($this->once())->method('getTime')->will($this->returnValue(1));
        $table->shouldReceive('insert')->once()->with(['key' => 'prefixfoo', 'value' => serialize('bar'), 'expiration' => 61])->andReturnUsing(function () {
            throw new Exception;
        });
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('update')->once()->with(['value' => serialize('bar'), 'expiration' => 61]);

        $store->put('foo', 'bar', 1);
    }

    public function testForeverCallsStoreItemWithReallyLongTime(): void
    {
        $store = $this->getMockBuilder('Illuminate\Cache\DatabaseStore')->setMethods(['put'])->setConstructorArgs($this->getMocks())->getMock();
        $store->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(5256000));
        $store->forever('foo', 'bar');
    }

    public function testItemsMayBeRemovedFromCache(): void
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('delete')->once();

        $store->forget('foo');
    }

    public function testItemsMayBeFlushedFromCache(): void
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('delete')->once()->andReturn(2);

        $result = $store->flush();
        $this->assertTrue($result);
    }

    public function testIncrementReturnsCorrectValues(): void
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');
        $cache = m::mock('stdClass');

        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type('Closure'))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn(null);
        $this->assertFalse($store->increment('foo'));

        $cache->value = serialize('bar');
        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type('Closure'))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn($cache);
        $this->assertFalse($store->increment('foo'));

        $cache->value = serialize(2);
        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type('Closure'))->andReturnUsing(function ($closure) {
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

    public function testDecrementReturnsCorrectValues(): void
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');
        $cache = m::mock('stdClass');

        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type('Closure'))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn(null);
        $this->assertFalse($store->decrement('foo'));

        $cache->value = serialize('bar');
        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type('Closure'))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn($cache);
        $this->assertFalse($store->decrement('foo'));

        $cache->value = serialize(3);
        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type('Closure'))->andReturnUsing(function ($closure) {
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
        return new DatabaseStore(m::mock('Illuminate\Database\Connection'), 'table', 'prefix');
    }

    protected function getMocks()
    {
        return [m::mock('Illuminate\Database\Connection'), 'table', 'prefix'];
    }
}
