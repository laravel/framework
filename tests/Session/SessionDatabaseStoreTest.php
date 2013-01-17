<?php

use Mockery as m;
use Illuminate\Session\DatabaseStore;

class SessionDatabaseStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testRetrieveSessionReturnsDecryptedPayload()
	{
		$store = $this->getStore();
		$table = m::mock('StdClass');
		$store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
		$table->shouldReceive('find')->once()->with(1)->andReturn((object) array('payload' => 'bar'));
		$store->getEncrypter()->shouldReceive('decrypt')->once()->with('bar')->andReturn('decrypted.bar');

		$this->assertEquals('decrypted.bar', $store->retrieveSession(1));
	}


	public function testRetrieveSessionReturnsNullWhenSessionNotFound()
	{
		$store = $this->getStore();
		$table = m::mock('StdClass');
		$store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
		$table->shouldReceive('find')->once()->with(1)->andReturn(null);
		$store->getEncrypter()->shouldReceive('decrypt')->never();

		$this->assertNull($store->retrieveSession(1));
	}


	public function testCreateSessionStoresEncryptedPayload()
	{
		$store = $this->getStore();
		$table = m::mock('StdClass');
		$store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
		$store->getEncrypter()->shouldReceive('encrypt')->once()->with(array('session', 'last_activity' => 100))->andReturn(array('encrypted.session'));
		$table->shouldReceive('insert')->once()->with(array('id' => 1, 'payload' => array('encrypted.session'), 'last_activity' => 100));

		$store->createSession(1, array('session', 'last_activity' => 100), new Symfony\Component\HttpFoundation\Response);
	}


	public function testUpdateSessionStoresEncryptedPayload()
	{
		$store = $this->getStore();
		$table = m::mock('StdClass');
		$store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
		$store->getEncrypter()->shouldReceive('encrypt')->once()->with(array('session', 'last_activity' => 100))->andReturn(array('encrypted.session'));
		$table->shouldReceive('where')->once()->with('id', '=', 1)->andReturn($table);
		$table->shouldReceive('update')->once()->with(array('payload' => array('encrypted.session'), 'last_activity' => 100));

		$store->updateSession(1, array('session', 'last_activity' => 100), new Symfony\Component\HttpFoundation\Response);
	}


	public function testSweepRemovesExpiredSessions()
	{
		$store = $this->getStore();
		$table = m::mock('StdClass');
		$store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
		$table->shouldReceive('where')->once()->with('last_activity', '<', 100)->andReturn($table);
		$table->shouldReceive('delete')->once();

		$store->sweep(100);
	}


	protected function getStore()
	{
		return new DatabaseStore(m::mock('Illuminate\Database\Connection'), m::mock('Illuminate\Encryption\Encrypter'), 'table');
	}

}