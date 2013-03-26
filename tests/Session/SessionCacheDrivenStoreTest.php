<?php

use Mockery as m;
use Symfony\Component\HttpFoundation\Response;

class SessionCacheDrivenStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testRetrieveCallsCache()
	{
		$cache = $this->getCacheMock();
		$store = new Illuminate\Session\CacheDrivenStore($cache);
		$cache->shouldReceive('get')->once()->with('foo')->andReturn('bar');
		$this->assertEquals('bar', $store->retrieveSession('foo'));
	}


	public function testCreateSessionCallsCache()
	{
		$cache = $this->getCacheMock();
		$store = new Illuminate\Session\CacheDrivenStore($cache);
		$cache->shouldReceive('forever')->once()->with('foo', array('bar'));
		$store->createSession('foo', array('bar'), new Response);
	}


	public function testUpdateSessionCallsCreateSession()
	{
		$mock = $this->getCacheMock();
		$cache = $this->getMock('Illuminate\Session\CacheDrivenStore', array('createSession'), array($mock));
		$cache->expects($this->once())->method('createSession');
		$cache->updateSession('foo', array('bar'), new Response);
	}


	protected function getCacheMock()
	{
		return m::mock('Illuminate\Cache\Repository');
	}

}