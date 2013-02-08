<?php

use Mockery as m;
use Illuminate\Cookie\CookieJar;
use Illuminate\Session\CookieStore;
use Symfony\Component\HttpFoundation\Request;

class SessionCookieStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testRetrieveSessionProperlyRetrievesCookie()
	{
		$store = new CookieStore($cookies = m::mock('Illuminate\Cookie\CookieJar'));
		$cookies->shouldReceive('get')->once()->with('name')->andReturn(1);
		$cookies->shouldReceive('get')->once()->with('illuminate_payload')->andReturn(serialize($expect = array('id' => '1', 'data' => array('foo' => 'bar'), 'last_activity' => '9999999999')));
		$store->start($cookies, 'name');
		$this->assertEquals($expect, $store->getSession());
	}


	public function testCreateSessionStoresCookiePayload()
	{
		$store = new Illuminate\Session\CookieStore($cookie = m::mock('Illuminate\Cookie\CookieJar'));
		$session = array('id' => '1', 'data' => array(':old:' => array(), ':new:' => array()));
		$cookie->shouldReceive('make')->once()
			->with('illuminate_payload', serialize($session))
			->andReturn(new Symfony\Component\HttpFoundation\Cookie('illuminate_payload', serialize($session)));
		$store->setSession($session);
		$store->setExists(false);
		$response = new Symfony\Component\HttpFoundation\Response;
		$store->createSession(1, $session, $response);

		$this->assertTrue(count($response->headers->getCookies()) == 1);
		$cookies = $response->headers->getCookies();
		$this->assertEquals(serialize($session), $cookies[0]->getValue());		
	}


	public function testUpdateSessionCallsCreateSession()
	{
		$store = $this->storeMock(array('createSession'), 'Illuminate\Session\CookieStore', array($this->getCookieJar()));
		$session = array('id' => '1', 'data' => array(':old:' => array(), ':new:' => array()));
		$store->setSession($session);
		$store->expects($this->once())->method('createSession');
		$store->setExists(true);
		$store->updateSession(1, array(), new Symfony\Component\HttpFoundation\Response());
	}


	protected function dummySession()
	{
		return array('id' => '123', 'data' => array(':old:' => array(), ':new:' => array()), 'last_activity' => '9999999999');
	}


	protected function storeMock($stub = array(), $class = 'Illuminate\Session\Store', $constructor = null)
	{
		return $this->getMock($class, $stub, $constructor);
	}


	protected function getCookieJar()
	{
		return new Illuminate\Cookie\CookieJar(Request::create('/foo', 'GET'), m::mock('Illuminate\Encryption\Encrypter'), array('path' => '/', 'domain' => 'foo.com', 'secure' => true, 'httpOnly' => true));
	}

}