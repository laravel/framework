<?php

use Mockery as m;
use Symfony\Component\HttpFoundation\Request;

class AuthGuardTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicReturnsNullOnValidAttempt()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$guard = m::mock('Illuminate\Auth\Guard[check,attempt]', array($provider, $session));
		$guard->shouldReceive('check')->once()->andReturn(false);
		$guard->shouldReceive('attempt')->once()->with(array('email' => 'foo@bar.com', 'password' => 'secret'))->andReturn(true);
		$request = Symfony\Component\HttpFoundation\Request::create('/', 'GET', array(), array(), array(), array('PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret'));

		$guard->basic('email', $request);
	}


	public function testBasicReturnsNullWhenAlreadyLoggedIn()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$guard = m::mock('Illuminate\Auth\Guard[check]', array($provider, $session));
		$guard->shouldReceive('check')->once()->andReturn(true);
		$guard->shouldReceive('attempt')->never();
		$request = Symfony\Component\HttpFoundation\Request::create('/', 'GET', array(), array(), array(), array('PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret'));

		$guard->basic('email', $request);
	}


	public function testBasicReturnsResponseOnFailure()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$guard = m::mock('Illuminate\Auth\Guard[check,attempt]', array($provider, $session));
		$guard->shouldReceive('check')->once()->andReturn(false);
		$guard->shouldReceive('attempt')->once()->with(array('email' => 'foo@bar.com', 'password' => 'secret'))->andReturn(false);
		$request = Symfony\Component\HttpFoundation\Request::create('/', 'GET', array(), array(), array(), array('PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret'));
		$response = $guard->basic('email', $request);

		$this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
		$this->assertEquals(401, $response->getStatusCode());
	}


	public function testAttemptCallsRetrieveByCredentials()
	{
		$guard = $this->getGuard();
		$guard->setDispatcher($events = m::mock('Illuminate\Events\Dispatcher'));
		$events->shouldReceive('fire')->once()->with('auth.attempt', array(array('foo'), false, true));
		$guard->getProvider()->shouldReceive('retrieveByCredentials')->once()->with(array('foo'));
		$guard->attempt(array('foo'));
	}


	public function testAttemptReturnsUserInterface()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$guard = $this->getMock('Illuminate\Auth\Guard', array('login'), array($provider, $session, $request));
		$guard->setDispatcher($events = m::mock('Illuminate\Events\Dispatcher'));
		$events->shouldReceive('fire')->once()->with('auth.attempt', array(array('foo'), false, true));
		$user = $this->getMock('Illuminate\Auth\UserInterface');
		$guard->getProvider()->shouldReceive('retrieveByCredentials')->once()->andReturn($user);
		$guard->getProvider()->shouldReceive('validateCredentials')->with($user, array('foo'))->andReturn(true);
		$guard->expects($this->once())->method('login')->with($this->equalTo($user));
		$this->assertTrue($guard->attempt(array('foo')));
	}


	public function testAttemptReturnsFalseIfUserNotGiven()
	{
		$mock = $this->getGuard();
		$mock->setDispatcher($events = m::mock('Illuminate\Events\Dispatcher'));
		$events->shouldReceive('fire')->once()->with('auth.attempt', array(array('foo'), false, true));
		$mock->getProvider()->shouldReceive('retrieveByCredentials')->once()->andReturn(null);
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
		$session->shouldReceive('migrate')->once();
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
		$session->shouldReceive('migrate')->once();
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
		$mock->getSession()->shouldReceive('get')->once()->andReturn(null);
		$this->assertNull($mock->user());
	}


	public function testUserIsSetToRetrievedUser()
	{
		$mock = $this->getGuard();
		$mock->getSession()->shouldReceive('get')->once()->andReturn(1);
		$user = m::mock('Illuminate\Auth\UserInterface');
		$mock->getProvider()->shouldReceive('retrieveById')->once()->with(1)->andReturn($user);
		$this->assertEquals($user, $mock->user());
		$this->assertEquals($user, $mock->getUser());
	}


	public function testLogoutRemovesSessionTokenAndRememberMeCookie()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$mock = $this->getMock('Illuminate\Auth\Guard', array('getName', 'getRecallerName'), array($provider, $session, $request));
		$mock->setCookieJar($cookies = m::mock('Illuminate\Cookie\CookieJar'));
		$user = m::mock('Illuminate\Auth\UserInterface');
		$user->shouldReceive('setRememberToken')->once();
		$mock->expects($this->once())->method('getName')->will($this->returnValue('foo'));
		$mock->expects($this->once())->method('getRecallerName')->will($this->returnValue('bar'));
		$provider->shouldReceive('updateRememberToken')->once();

		$cookie = m::mock('Symfony\Component\HttpFoundation\Cookie');
		$cookies->shouldReceive('forget')->once()->with('bar')->andReturn($cookie);
		$cookies->shouldReceive('queue')->once()->with($cookie);
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
		$user->shouldReceive('setRememberToken')->once();
		$provider->shouldReceive('updateRememberToken')->once();
		$mock->setUser($user);
		$events->shouldReceive('fire')->once()->with('auth.logout', array($user));
		$mock->logout();
	}


	public function testLoginMethodQueuesCookieWhenRemembering()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$guard = new Illuminate\Auth\Guard($provider, $session, $request);
		$guard->setCookieJar($cookie);
		$foreverCookie = new Symfony\Component\HttpFoundation\Cookie($guard->getRecallerName(), 'foo');
		$cookie->shouldReceive('forever')->once()->with($guard->getRecallerName(), 'foo|recaller')->andReturn($foreverCookie);
		$cookie->shouldReceive('queue')->once()->with($foreverCookie);
		$guard->getSession()->shouldReceive('put')->once()->with($guard->getName(), 'foo');
		$session->shouldReceive('migrate')->once();
		$user = m::mock('Illuminate\Auth\UserInterface');
		$user->shouldReceive('getAuthIdentifier')->andReturn('foo');
		$user->shouldReceive('getRememberToken')->andReturn('recaller');
		$user->shouldReceive('setRememberToken')->never();
		$provider->shouldReceive('updateRememberToken')->never();
		$guard->login($user, true);
	}


	public function testLoginMethodCreatesRememberTokenIfOneDoesntExist()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$guard = new Illuminate\Auth\Guard($provider, $session, $request);
		$guard->setCookieJar($cookie);
		$foreverCookie = new Symfony\Component\HttpFoundation\Cookie($guard->getRecallerName(), 'foo');
		$cookie->shouldReceive('forever')->once()->andReturn($foreverCookie);
		$cookie->shouldReceive('queue')->once()->with($foreverCookie);
		$guard->getSession()->shouldReceive('put')->once()->with($guard->getName(), 'foo');
		$session->shouldReceive('migrate')->once();
		$user = m::mock('Illuminate\Auth\UserInterface');
		$user->shouldReceive('getAuthIdentifier')->andReturn('foo');
		$user->shouldReceive('getRememberToken')->andReturn(null);
		$user->shouldReceive('setRememberToken')->once();
		$provider->shouldReceive('updateRememberToken')->once();
		$guard->login($user, true);
	}


	public function testLoginUsingIdStoresInSessionAndLogsInWithUser()
	{
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$guard = $this->getMock('Illuminate\Auth\Guard', array('login', 'user'), array($provider, $session, $request));
		$guard->getSession()->shouldReceive('put')->once()->with($guard->getName(), 10);
		$guard->getProvider()->shouldReceive('retrieveById')->once()->with(10)->andReturn($user = m::mock('Illuminate\Auth\UserInterface'));
		$guard->expects($this->once())->method('login')->with($this->equalTo($user), $this->equalTo(false))->will($this->returnValue($user));

		$this->assertEquals($user, $guard->loginUsingId(10));
	}


	public function testUserUsesRememberCookieIfItExists()
	{
		$guard = $this->getGuard();
		list($session, $provider, $request, $cookie) = $this->getMocks();
		$request = Symfony\Component\HttpFoundation\Request::create('/', 'GET', array(), array($guard->getRecallerName() => 'id|recaller'));
		$guard = new Illuminate\Auth\Guard($provider, $session, $request);
		$guard->getSession()->shouldReceive('get')->once()->with($guard->getName())->andReturn(null);
		$user = m::mock('Illuminate\Auth\UserInterface');
		$guard->getProvider()->shouldReceive('retrieveByToken')->once()->with('id', 'recaller')->andReturn($user);
		$this->assertEquals($user, $guard->user());
		$this->assertTrue($guard->viaRemember());
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
