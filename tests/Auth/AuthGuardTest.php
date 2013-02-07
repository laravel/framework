<?php

use Mockery as m;
use Symfony\Component\HttpFoundation\Request;

class AuthGuardTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testAttemptCallsRetrieveByCredentials()
	{
		$guard = $this->getGuard();
		$guard->getProvider()->shouldReceive('retrieveByCredentials')->once()->with(array('foo'));
		$guard->attempt(array('foo'));
	}


	public function testAttemptReturnsUserInterface()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$guard = $this->getMock('Illuminate\Auth\Guard', array('login'), array($provider, $session, $request));
		$user = $this->getMock('Illuminate\Auth\UserInterface');
		$guard->getProvider()->shouldReceive('retrieveByCredentials')->once()->andReturn($user);
		$guard->getProvider()->shouldReceive('validateCredentials')->with($user, array('foo'))->andReturn(true);
		$guard->expects($this->once())->method('login')->with($this->equalTo($user));
		$this->assertTrue($guard->attempt(array('foo')));
	}


	public function testAttemptReturnsFalseIfUserNotGiven()
	{
		$mock = $this->getGuard();
		$mock->getProvider()->shouldReceive('retrieveByCredentials')->once()->andReturn('foo');
		$this->assertFalse($mock->attempt(array('foo')));
	}


	public function testLoginStoresIdentifierInSession()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$mock = $this->getMock('Illuminate\Auth\Guard', array('getName'), array($provider, $session, $request));
		$user = m::mock('Illuminate\Auth\UserInterface');
		$mock->expects($this->once())->method('getName')->will($this->returnValue('foo'));
		$user->shouldReceive('getAuthIdentifier')->once()->andReturn('bar');
		$mock->getSession()->shouldReceive('put')->with('foo', 'bar')->once();
		$mock->login($user);
	}


	public function testLoginFiresLoginEvent()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$mock = $this->getMock('Illuminate\Auth\Guard', array('getName'), array($provider, $session, $request));
		$mock->setDispatcher($events = m::mock('Illuminate\Events\Dispatcher'));
		$user = m::mock('Illuminate\Auth\UserInterface');
		$events->shouldReceive('fire')->once()->with('auth.login', array($user, false));
		$mock->expects($this->once())->method('getName')->will($this->returnValue('foo'));
		$user->shouldReceive('getAuthIdentifier')->once()->andReturn('bar');
		$mock->getSession()->shouldReceive('put')->with('foo', 'bar')->once();
		$mock->login($user);
	}


	public function testIsAuthedReturnsTrueWhenUserIsNotNull()
	{
		$user = m::mock('Illuminate\Auth\UserInterface');
		$mock = $this->getGuard();
		$mock->setUser($user);
		$this->assertTrue($mock->check());
		$this->assertFalse($mock->guest());
	}


	public function testIsAuthedReturnsFalseWhenUserIsNull()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$mock = $this->getMock('Illuminate\Auth\Guard', array('user'), array($provider, $session, $request));
		$mock->expects($this->exactly(2))->method('user')->will($this->returnValue(null));
		$this->assertFalse($mock->check());
		$this->assertTrue($mock->guest());
	}


	public function testUserMethodReturnsCachedUser()
	{
		$user = m::mock('Illuminate\Auth\UserInterface');
		$mock = $this->getGuard();
		$mock->setUser($user);
		$this->assertEquals($user, $mock->user());
	}


	public function testNullIsReturnedForUserIfNoUserFound()
	{
		$mock = $this->getGuard();
		$mock->setCookieJar($cookies = m::mock('Illuminate\Cookie\CookieJar'));
		$cookies->shouldReceive('get')->once()->andReturn(null);
		$mock->getSession()->shouldReceive('get')->once()->andReturn(null);
		$this->assertNull($mock->user());
	}


	public function testUserIsSetToRetrievedUser()
	{
		$mock = $this->getGuard();
		$mock->getSession()->shouldReceive('get')->once()->andReturn(1);
		$user = m::mock('Illuminate\Auth\UserInterface');
		$mock->getProvider()->shouldReceive('retrieveByID')->once()->with(1)->andReturn($user);
		$this->assertEquals($user, $mock->user());
		$this->assertEquals($user, $mock->getUser());
	}


	public function testLogoutRemovesSessionTokenAndRememberMeCookie()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$mock = $this->getMock('Illuminate\Auth\Guard', array('getName', 'getRecallerName'), array($provider, $session, $request));
		$mock->setCookieJar($cookies = m::mock('Illuminate\Cookie\CookieJar'));
		$user = m::mock('Illuminate\Auth\UserInterface');
		$mock->expects($this->once())->method('getName')->will($this->returnValue('foo'));
		$mock->expects($this->once())->method('getRecallerName')->will($this->returnValue('bar'));
		$cookies->shouldReceive('forget')->once()->with('bar');
		$mock->getSession()->shouldReceive('forget')->once()->with('foo');
		$mock->setUser($user);
		$mock->logout();
		$this->assertNull($mock->getUser());
	}


	public function testLogoutFiresLogoutEvent()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$mock = $this->getMock('Illuminate\Auth\Guard', array('clearUserDataFromStorage'), array($provider, $session, $request));
		$mock->expects($this->once())->method('clearUserDataFromStorage');
		$mock->setDispatcher($events = m::mock('Illuminate\Events\Dispatcher'));
		$user = m::mock('Illuminate\Auth\UserInterface');
		$mock->setUser($user);
		$events->shouldReceive('fire')->once()->with('auth.logout', array($user));
		$mock->logout();
	}


	public function testLoginMethodQueuesCookieWhenRemembering()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$cookie = m::mock('Illuminate\Cookie\CookieJar');
		$guard = new Illuminate\Auth\Guard($provider, $session, $request);
		$guard->setCookieJar($cookie);
		$cookie->shouldReceive('forever')->once()->with($guard->getRecallerName(), 'foo')->andReturn(new Symfony\Component\HttpFoundation\Cookie($guard->getRecallerName(), 'foo'));
		$guard->getSession()->shouldReceive('put')->once()->with($guard->getName(), 'foo');
		$user = m::mock('Illuminate\Auth\UserInterface');
		$user->shouldReceive('getAuthIdentifier')->once()->andReturn('foo');
		$guard->login($user, true);

		$cookies = $guard->getQueuedCookies();
		$this->assertEquals(1, count($cookies));
		$this->assertEquals('foo', $cookies[0]->getValue());
		$this->assertEquals($cookies[0]->getName(), $guard->getRecallerName());
	}


	public function testLoginUsingIdStoresInSessionAndLogsInWithUser()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$guard = $this->getMock('Illuminate\Auth\Guard', array('login', 'user'), array($provider, $session, $request));
		$guard->getSession()->shouldReceive('put')->once()->with($guard->getName(), 10);
		$guard->expects($this->once())->method('user')->will($this->returnValue($user = m::mock('Illuminate\Auth\UserInterface')));
		$guard->expects($this->once())->method('login')->with($this->equalTo($user), $this->equalTo(false))->will($this->returnValue($user));

		$this->assertEquals($user, $guard->loginUsingId(10));
	}


	public function testUserUsesRememberCookieIfItExists()
	{
		$guard = $this->getGuard();
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$request = Symfony\Component\HttpFoundation\Request::create('/', 'GET', array(), array($guard->getRecallerName() => 'recaller'));
		$guard = new Illuminate\Auth\Guard($provider, $session, $request);
		$cookie = m::mock('Illuminate\Cookie\CookieJar');
		$guard->setCookieJar($cookie);
		$cookie->shouldReceive('get')->once()->with($guard->getRecallerName())->andReturn('recaller');
		$guard->getSession()->shouldReceive('get')->once()->with($guard->getName())->andReturn(null);
		$user = m::mock('Illuminate\Auth\UserInterface');
		$guard->getProvider()->shouldReceive('retrieveByID')->once()->with('recaller')->andReturn($user);
		$this->assertEquals($user, $guard->user());
	}


	protected function getGuard()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		return new Illuminate\Auth\Guard($provider, $session, $request);
	}


	protected function getMocks()
	{
		return array(
			m::mock('Illuminate\Session\Store'),
			m::mock('Illuminate\Auth\UserProviderInterface'),
			Symfony\Component\HttpFoundation\Request::create('/', 'GET'),
			m::mock('Illuminate\Cookie\CookieJar'),
		);
	}


	protected function getCookieJar()
	{
		return new Illuminate\Cookie\CookieJar(Request::create('/foo', 'GET'), m::mock('Illuminate\Encryption\Encrypter'), array('domain' => 'foo.com', 'path' => '/', 'secure' => false, 'httpOnly' => false));
	}

}