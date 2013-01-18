<?php

use Symfony\Component\HttpFoundation\Response;

class SessionCacheDrivenStoreTest extends PHPUnit_Framework_TestCase {

	public function testRetrieveCallsCache()
	{
		$cache = $this->getCacheMock();
		$store = new Illuminate\Session\CacheDrivenStore($cache);
		$cache->expects($this->once())->method('get')->with($this->equalTo('foo'))->will($this->returnValue('bar'));
		$this->assertEquals('bar', $store->retrieveSession('foo'));
	}


	public function testCreateSessionCallsCache()
	{
		$cache = $this->getCacheMock();
		$store = new Illuminate\Session\CacheDrivenStore($cache);
		$cache->expects($this->once())->method('forever')->with($this->equalTo('foo'), $this->equalTo(array('bar')));
		$store->createSession('foo', array('bar'), new Response);
	}


	public function testUpdateSessionCallsCreateSession()
	{
		$cache = $this->getCacheMock();
		$cache = $this->getMock('Illuminate\Session\CacheDrivenStore', array('createSession'), array($cache));
		$cache->expects($this->once())->method('createSession');
		$cache->updateSession('foo', array('bar'), new Response);
	}


	protected function getCacheMock()
	{
		return $this->getMock('Illuminate\Cache\Store', array('get', 'forever', 'retrieveItem', 'storeItem', 'storeItemForever', 'removeItem', 'flushItems'));
	}

}