<?php

use Mockery as m;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Auth\Events\Attempting;

class AuthGuardTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBasicReturnsNullOnValidAttempt()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $guard = m::mock('Illuminate\Auth\SessionGuard[check,attempt]', ['default', $provider, $session]);
        $guard->shouldReceive('check')->once()->andReturn(false);
        $guard->shouldReceive('attempt')->once()->with(['email' => 'foo@bar.com', 'password' => 'secret'])->andReturn(true);
        $request = Symfony\Component\HttpFoundation\Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);

        $guard->basic('email');
    }

    public function testBasicReturnsNullWhenAlreadyLoggedIn()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $guard = m::mock('Illuminate\Auth\SessionGuard[check]', ['default', $provider, $session]);
        $guard->shouldReceive('check')->once()->andReturn(true);
        $guard->shouldReceive('attempt')->never();
        $request = Symfony\Component\HttpFoundation\Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);

        $guard->basic('email', $request);
    }

    public function testBasicReturnsResponseOnFailure()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $guard = m::mock('Illuminate\Auth\SessionGuard[check,attempt]', ['default', $provider, $session]);
        $guard->shouldReceive('check')->once()->andReturn(false);
        $guard->shouldReceive('attempt')->once()->with(['email' => 'foo@bar.com', 'password' => 'secret'])->andReturn(false);
        $request = Symfony\Component\HttpFoundation\Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);
        $response = $guard->basic('email', $request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testAttemptCallsRetrieveByCredentials()
    {
        $guard = $this->getGuard();
        $guard->setDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('fire')->once()->with(m::type(Attempting::class));
        $guard->getProvider()->shouldReceive('retrieveByCredentials')->once()->with(['foo']);
        $guard->attempt(['foo']);
    }

    public function testAttemptReturnsUserInterface()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $guard = $this->getMock('Illuminate\Auth\SessionGuard', ['login'], ['default', $provider, $session, $request]);
        $guard->setDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('fire')->once()->with(m::type(Attempting::class));
        $user = $this->getMock('Illuminate\Contracts\Auth\Authenticatable');
        $guard->getProvider()->shouldReceive('retrieveByCredentials')->once()->andReturn($user);
        $guard->getProvider()->shouldReceive('validateCredentials')->with($user, ['foo'])->andReturn(true);
        $guard->expects($this->once())->method('login')->with($this->equalTo($user));
        $this->assertTrue($guard->attempt(['foo']));
    }

    public function testAttemptReturnsFalseIfUserNotGiven()
    {
        $mock = $this->getGuard();
        $mock->setDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $events->shouldReceive('fire')->once()->with(m::type(Attempting::class));
        $mock->getProvider()->shouldReceive('retrieveByCredentials')->once()->andReturn(null);
        $this->assertFalse($mock->attempt(['foo']));
    }

    public function testLoginStoresIdentifierInSession()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $mock = $this->getMock('Illuminate\Auth\SessionGuard', ['getName'], ['default', $provider, $session, $request]);
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $mock->expects($this->once())->method('getName')->will($this->returnValue('foo'));
        $user->shouldReceive('getAuthIdentifier')->once()->andReturn('bar');
        $mock->getSession()->shouldReceive('set')->with('foo', 'bar')->once();
        $session->shouldReceive('migrate')->once();
        $mock->login($user);
    }

    public function testLoginFiresLoginEvent()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $mock = $this->getMock('Illuminate\Auth\SessionGuard', ['getName'], ['default', $provider, $session, $request]);
        $mock->setDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $events->shouldReceive('fire')->once()->with(m::type('Illuminate\Auth\Events\Login'));
        $mock->expects($this->once())->method('getName')->will($this->returnValue('foo'));
        $user->shouldReceive('getAuthIdentifier')->once()->andReturn('bar');
        $mock->getSession()->shouldReceive('set')->with('foo', 'bar')->once();
        $session->shouldReceive('migrate')->once();
        $mock->login($user);
    }

    public function testIsAuthedReturnsTrueWhenUserIsNotNull()
    {
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $mock = $this->getGuard();
        $mock->setUser($user);
        $this->assertTrue($mock->check());
        $this->assertFalse($mock->guest());
    }

    public function testIsAuthedReturnsFalseWhenUserIsNull()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $mock = $this->getMock('Illuminate\Auth\SessionGuard', ['user'], ['default', $provider, $session, $request]);
        $mock->expects($this->exactly(2))->method('user')->will($this->returnValue(null));
        $this->assertFalse($mock->check());
        $this->assertTrue($mock->guest());
    }

    public function testUserMethodReturnsCachedUser()
    {
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
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
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $mock->getProvider()->shouldReceive('retrieveById')->once()->with(1)->andReturn($user);
        $this->assertEquals($user, $mock->user());
        $this->assertEquals($user, $mock->getUser());
    }

    public function testLogoutRemovesSessionTokenAndRememberMeCookie()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $mock = $this->getMock('Illuminate\Auth\SessionGuard', ['getName', 'getRecallerName'], ['default', $provider, $session, $request]);
        $mock->setCookieJar($cookies = m::mock('Illuminate\Cookie\CookieJar'));
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $user->shouldReceive('setRememberToken')->once();
        $mock->expects($this->once())->method('getName')->will($this->returnValue('foo'));
        $mock->expects($this->once())->method('getRecallerName')->will($this->returnValue('bar'));
        $provider->shouldReceive('updateRememberToken')->once();

        $cookie = m::mock('Symfony\Component\HttpFoundation\Cookie');
        $cookies->shouldReceive('forget')->once()->with('bar')->andReturn($cookie);
        $cookies->shouldReceive('queue')->once()->with($cookie);
        $mock->getSession()->shouldReceive('remove')->once()->with('foo');
        $mock->setUser($user);
        $mock->logout();
        $this->assertNull($mock->getUser());
    }

    public function testLogoutFiresLogoutEvent()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $mock = $this->getMock('Illuminate\Auth\SessionGuard', ['clearUserDataFromStorage'], ['default', $provider, $session, $request]);
        $mock->expects($this->once())->method('clearUserDataFromStorage');
        $mock->setDispatcher($events = m::mock('Illuminate\Contracts\Events\Dispatcher'));
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $user->shouldReceive('setRememberToken')->once();
        $provider->shouldReceive('updateRememberToken')->once();
        $mock->setUser($user);
        $events->shouldReceive('fire')->once()->with(m::type('Illuminate\Auth\Events\Logout'));
        $mock->logout();
    }

    public function testLoginMethodQueuesCookieWhenRemembering()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $guard = new Illuminate\Auth\SessionGuard('default', $provider, $session, $request);
        $guard->setCookieJar($cookie);
        $foreverCookie = new Symfony\Component\HttpFoundation\Cookie($guard->getRecallerName(), 'foo');
        $cookie->shouldReceive('forever')->once()->with($guard->getRecallerName(), 'foo|recaller')->andReturn($foreverCookie);
        $cookie->shouldReceive('queue')->once()->with($foreverCookie);
        $guard->getSession()->shouldReceive('set')->once()->with($guard->getName(), 'foo');
        $session->shouldReceive('migrate')->once();
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $user->shouldReceive('getAuthIdentifier')->andReturn('foo');
        $user->shouldReceive('getRememberToken')->andReturn('recaller');
        $user->shouldReceive('setRememberToken')->never();
        $provider->shouldReceive('updateRememberToken')->never();
        $guard->login($user, true);
    }

    public function testLoginMethodCreatesRememberTokenIfOneDoesntExist()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $guard = new Illuminate\Auth\SessionGuard('default', $provider, $session, $request);
        $guard->setCookieJar($cookie);
        $foreverCookie = new Symfony\Component\HttpFoundation\Cookie($guard->getRecallerName(), 'foo');
        $cookie->shouldReceive('forever')->once()->andReturn($foreverCookie);
        $cookie->shouldReceive('queue')->once()->with($foreverCookie);
        $guard->getSession()->shouldReceive('set')->once()->with($guard->getName(), 'foo');
        $session->shouldReceive('migrate')->once();
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $user->shouldReceive('getAuthIdentifier')->andReturn('foo');
        $user->shouldReceive('getRememberToken')->andReturn(null);
        $user->shouldReceive('setRememberToken')->once();
        $provider->shouldReceive('updateRememberToken')->once();
        $guard->login($user, true);
    }

    public function testLoginUsingIdStoresInSessionAndLogsInWithUser()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $guard = $this->getMock('Illuminate\Auth\SessionGuard', ['login', 'user'], ['default', $provider, $session, $request]);
        $guard->getSession()->shouldReceive('set')->once()->with($guard->getName(), 10);
        $guard->getProvider()->shouldReceive('retrieveById')->once()->with(10)->andReturn($user = m::mock('Illuminate\Contracts\Auth\Authenticatable'));
        $guard->expects($this->once())->method('login')->with($this->equalTo($user), $this->equalTo(false))->will($this->returnValue($user));

        $this->assertEquals($user, $guard->loginUsingId(10));
    }

    public function testOnceUsingIdFailure()
    {
        $guard = $this->getGuard();
        $guard->getProvider()->shouldReceive('retrieveById')->once()->with(11)->andReturn(null);
        $this->assertFalse($guard->onceUsingId(11));
    }

    public function testUserUsesRememberCookieIfItExists()
    {
        $guard = $this->getGuard();
        list($session, $provider, $request, $cookie) = $this->getMocks();
        $request = Symfony\Component\HttpFoundation\Request::create('/', 'GET', [], [$guard->getRecallerName() => 'id|recaller']);
        $guard = new Illuminate\Auth\SessionGuard('default', $provider, $session, $request);
        $guard->getSession()->shouldReceive('get')->once()->with($guard->getName())->andReturn(null);
        $user = m::mock('Illuminate\Contracts\Auth\Authenticatable');
        $guard->getProvider()->shouldReceive('retrieveByToken')->once()->with('id', 'recaller')->andReturn($user);
        $user->shouldReceive('getAuthIdentifier')->once()->andReturn('bar');
        $guard->getSession()->shouldReceive('set')->with($guard->getName(), 'bar')->once();
        $session->shouldReceive('migrate')->once();
        $this->assertEquals($user, $guard->user());
        $this->assertTrue($guard->viaRemember());
    }

    protected function getGuard()
    {
        list($session, $provider, $request, $cookie) = $this->getMocks();

        return new Illuminate\Auth\SessionGuard('default', $provider, $session, $request);
    }

    protected function getMocks()
    {
        return [
            m::mock('Symfony\Component\HttpFoundation\Session\SessionInterface'),
            m::mock('Illuminate\Contracts\Auth\UserProvider'),
            Symfony\Component\HttpFoundation\Request::create('/', 'GET'),
            m::mock('Illuminate\Cookie\CookieJar'),
        ];
    }

    protected function getCookieJar()
    {
        return new Illuminate\Cookie\CookieJar(Request::create('/foo', 'GET'), m::mock('Illuminate\Contracts\Encryption\Encrypter'), ['domain' => 'foo.com', 'path' => '/', 'secure' => false, 'httpOnly' => false]);
    }
}
