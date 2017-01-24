<?php

namespace Illuminate\Tests\Cache;

use Exception;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\DatabaseStore;

class CacheDatabaseStoreTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testNullIsReturnedWhenItemNotFound()
    {
        $store = $this->getStore();
        $table = m::mock('StdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn(null);

        $this->assertNull($store->get('foo'));
    }

    public function testNullIsReturnedAndItemDeletedWhenItemIsExpired()
    {
        $store = $this->getMockBuilder('Illuminate\Cache\DatabaseStore')->setMethods(['forget'])->setConstructorArgs($this->getMocks())->getMock();
        $table = m::mock('StdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) ['expiration' => 1]);
        $store->expects($this->once())->method('forget')->with($this->equalTo('foo'))->will($this->returnValue(null));

        $this->assertNull($store->get('foo'));
    }

    public function testDecryptedValueIsReturnedWhenItemIsValid()
    {
        $store = $this->getStore();
        $table = m::mock('StdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) ['value' => 'bar', 'expiration' => 999999999999999]);
        $store->getEncrypter()->shouldReceive('decrypt')->once()->with('bar')->andReturn('bar');

        $this->assertEquals('bar', $store->get('foo'));
    }

    public function testEncryptedValueIsInsertedWhenNoExceptionsAreThrown()
    {
        $store = $this->getMockBuilder('Illuminate\Cache\DatabaseStore')->setMethods(['getTime'])->setConstructorArgs($this->getMocks())->getMock();
        $table = m::mock('StdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $store->getEncrypter()->shouldReceive('encrypt')->once()->with('bar')->andReturn('bar');
        $store->expects($this->once())->method('getTime')->will($this->returnValue(1));
        $table->shouldReceive('insert')->once()->with(['key' => 'prefixfoo', 'value' => 'bar', 'expiration' => 61]);

        $store->put('foo', 'bar', 1);
    }

    public function testEncryptedValueIsUpdatedWhenInsertThrowsException()
    {
        $store = $this->getMockBuilder('Illuminate\Cache\DatabaseStore')->setMethods(['getTime'])->setConstructorArgs($this->getMocks())->getMock();
        $table = m::mock('StdClass');
        $store->getConnection()->shouldReceive('table')->with('table')->andReturn($table);
        $store->getEncrypter()->shouldReceive('encrypt')->once()->with('bar')->andReturn('bar');
        $store->expects($this->once())->method('getTime')->will($this->returnValue(1));
        $table->shouldReceive('insert')->once()->with(['key' => 'prefixfoo', 'value' => 'bar', 'expiration' => 61])->andReturnUsing(function () {
            throw new Exception;
        });
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('update')->once()->with(['value' => 'bar', 'expiration' => 61]);

        $store->put('foo', 'bar', 1);
    }

    public function testForeverCallsStoreItemWithReallyLongTime()
    {
        $store = $this->getMockBuilder('Illuminate\Cache\DatabaseStore')->setMethods(['put'])->setConstructorArgs($this->getMocks())->getMock();
        $store->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(5256000));
        $store->forever('foo', 'bar');
    }

    public function testItemsMayBeRemovedFromCache()
    {
        $store = $this->getStore();
        $table = m::mock('StdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('delete')->once();

        $store->forget('foo');
    }

    public function testItemsMayBeFlushedFromCache()
    {
        $store = $this->getStore();
        $table = m::mock('StdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('delete')->once()->andReturn(2);

        $result = $store->flush();
        $this->assertTrue($result);
    }

    public function testIncrementReturnsCorrectValues()
    {
        $store = $this->getStore();
        $table = m::mock('StdClass');
        $cache = m::mock('StdClass');

        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type('Closure'))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn(null);
        $this->assertFalse($store->increment('foo'));

        $cache->value = 'bar';
        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type('Closure'))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn($cache);
        $store->getEncrypter()->shouldReceive('decrypt')->once()->with('bar')->andReturn('bar');
        $this->assertFalse($store->increment('foo'));

        $cache->value = 2;
        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type('Closure'))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn($cache);
        $store->getEncrypter()->shouldReceive('decrypt')->once()->with(2)->andReturn(2);
        $store->getEncrypter()->shouldReceive('encrypt')->once()->with(3)->andReturn(3);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('update')->once()->with(['value' => 3]);
        $this->assertEquals(3, $store->increment('foo'));
    }

    public function testDecrementReturnsCorrectValues()
    {
        $store = $this->getStore();
        $table = m::mock('StdClass');
        $cache = m::mock('StdClass');

        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type('Closure'))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn(null);
        $this->assertFalse($store->decrement('foo'));

        $cache->value = 'bar';
        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type('Closure'))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixfoo')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn($cache);
        $store->getEncrypter()->shouldReceive('decrypt')->once()->with('bar')->andReturn('bar');
        $this->assertFalse($store->decrement('foo'));

        $cache->value = 3;
        $store->getConnection()->shouldReceive('transaction')->once()->with(m::type('Closure'))->andReturnUsing(function ($closure) {
            return $closure();
        });
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixbar')->andReturn($table);
        $table->shouldReceive('lockForUpdate')->once()->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn($cache);
        $store->getEncrypter()->shouldReceive('decrypt')->once()->with(3)->andReturn(3);
        $store->getEncrypter()->shouldReceive('encrypt')->once()->with(2)->andReturn(2);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', 'prefixbar')->andReturn($table);
        $table->shouldReceive('update')->once()->with(['value' => 2]);
        $this->assertEquals(2, $store->decrement('bar'));
    }

    protected function getStore()
    {
        return new DatabaseStore(m::mock('Illuminate\Database\Connection'), m::mock('Illuminate\Contracts\Encryption\Encrypter'), 'table', 'prefix');
    }

    protected function getMocks()
    {
        return [m::mock('Illuminate\Database\Connection'), m::mock('Illuminate\Contracts\Encryption\Encrypter'), 'table', 'prefix'];
    }
}
