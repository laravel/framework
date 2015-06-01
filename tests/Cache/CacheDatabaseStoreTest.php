<?php

use Mockery as m;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Database\Connection;
use Illuminate\Contracts\Encryption\Encrypter;

class CacheDatabaseStoreTest extends PHPUnit_Framework_TestCase {

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
		$store = $this->getMock(DatabaseStore::class, array('forget'), $this->getMocks());
		$table = m::mock('StdClass');
		$store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
		$table->shouldReceive('where')->once()->with('key', '=', 'prefixfoo')->andReturn($table);
		$table->shouldReceive('first')->once()->andReturn((object) array('expiration' => 1));
		$store->expects($this->once())->method('forget')->with($this->equalTo('foo'))->will($this->returnValue(null));

		$this->assertNull($store->get('foo'));
	}


	public function testDecryptedValueIsReturnedWhenItemIsValid()
	{
		$store = $this->getStore();
		$table = m::mock('StdClass');
		$store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
		$table->shouldReceive('where')->once()->with('key', '=', 'prefixfoo')->andReturn($table);
		$table->shouldReceive('first')->once()->andReturn((object) array('value' => 'bar', 'expiration' => 999999999999999));
		$store->getEncrypter()->shouldReceive('decrypt')->once()->with('bar')->andReturn('bar');

		$this->assertEquals('bar', $store->get('foo'));
	}


	public function testEncryptedValueIsInsertedWhenNoExceptionsAreThrown()
	{
		$store = $this->getMock(DatabaseStore::class, array('getTime'), $this->getMocks());
		$table = m::mock('StdClass');
		$store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
		$store->getEncrypter()->shouldReceive('encrypt')->once()->with('bar')->andReturn('bar');
		$store->expects($this->once())->method('getTime')->will($this->returnValue(1));
		$table->shouldReceive('insert')->once()->with(array('key' => 'prefixfoo', 'value' => 'bar', 'expiration' => 61));

		$store->put('foo', 'bar', 1);
	}


	public function testEncryptedValueIsUpdatedWhenInsertThrowsException()
	{
		$store = $this->getMock(DatabaseStore::class, array('getTime'), $this->getMocks());
		$table = m::mock('StdClass');
		$store->getConnection()->shouldReceive('table')->with('table')->andReturn($table);
		$store->getEncrypter()->shouldReceive('encrypt')->once()->with('bar')->andReturn('bar');
		$store->expects($this->once())->method('getTime')->will($this->returnValue(1));
		$table->shouldReceive('insert')->once()->with(array('key' => 'prefixfoo', 'value' => 'bar', 'expiration' => 61))->andReturnUsing(function()
		{
			throw new Exception;
		});
		$table->shouldReceive('where')->once()->with('key', '=', 'prefixfoo')->andReturn($table);
		$table->shouldReceive('update')->once()->with(array('value' => 'bar', 'expiration' => 61));

		$store->put('foo', 'bar', 1);
	}


	public function testForeverCallsStoreItemWithReallyLongTime()
	{
		$store = $this->getMock(DatabaseStore::class, array('put'), $this->getMocks());
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
		$table->shouldReceive('delete')->once();

		$store->flush();
	}


	protected function getStore()
	{
		return new DatabaseStore(m::mock(Connection::class), m::mock(Encrypter::class), 'table', 'prefix');
	}


	protected function getMocks()
	{
		return array(m::mock(Connection::class), m::mock(Encrypter::class), 'table', 'prefix');
	}

}
