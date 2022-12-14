<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Validated;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Cookie\CookieJar;
use Illuminate\Support\Timebox;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBasicReturnsNullOnValidAttempt()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = m::mock(SessionGuard::class.'[check,attempt]', ['default', $provider, $session]);
        $guard->shouldReceive('check')->once()->andReturn(false);
        $guard->shouldReceive('attempt')->once()->with(['email' => 'foo@bar.com', 'password' => 'secret'])->andReturn(true);
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);

        $guard->basic('email');
    }

    public function testBasicReturnsNullWhenAlreadyLoggedIn()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = m::mock(SessionGuard::class.'[check]', ['default', $provider, $session]);
        $guard->shouldReceive('check')->once()->andReturn(true);
        $guard->shouldReceive('attempt')->never();
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);

        $guard->basic('email');
    }

    public function testBasicReturnsResponseOnFailure()
    {
        $this->expectException(UnauthorizedHttpException::class);

        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = m::mock(SessionGuard::class.'[check,attempt]', ['default', $provider, $session]);
        $guard->shouldReceive('check')->once()->andReturn(false);
        $guard->shouldReceive('attempt')->once()->with(['email' => 'foo@bar.com', 'password' => 'secret'])->andReturn(false);
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);
        $guard->basic('email');
    }

    public function testBasicWithExtraConditions()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = m::mock(SessionGuard::class.'[check,attempt]', ['default', $provider, $session]);
        $guard->shouldReceive('check')->once()->andReturn(false);
        $guard->shouldReceive('attempt')->once()->with(['email' => 'foo@bar.com', 'password' => 'secret', 'active' => 1])->andReturn(true);
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);

        $guard->basic('email', ['active' => 1]);
    }

    public function testBasicWithExtraArrayConditions()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = m::mock(SessionGuard::class.'[check,attempt]', ['default', $provider, $session]);
        $guard->shouldReceive('check')->once()->andReturn(false);
        $guard->shouldReceive('attempt')->once()->with(['email' => 'foo@bar.com', 'password' => 'secret', 'active' => 1, 'type' => [1, 2, 3]])->andReturn(true);
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);

        $guard->basic('email', ['active' => 1, 'type' => [1, 2, 3]]);
    }

    public function testAttemptCallsRetrieveByCredentials()
    {
        $guard = $this->getGuard();
        $guard->setDispatcher($events = m::mock(Dispatcher::class));
        $timebox = $guard->getTimebox();
        $timebox->shouldReceive('call')->once()->andReturnUsing(function ($callback) use ($timebox) {
            return $callback($timebox);
        });
        $events->shouldReceive('dispatch')->once()->with(m::type(Attempting::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(Failed::class));
        $events->shouldNotReceive('dispatch')->with(m::type(Validated::class));
        $guard->getProvider()->shouldReceive('retrieveByCredentials')->once()->with(['foo']);
        $guard->attempt(['foo']);
    }

    public function testAttemptReturnsUserInterface()
    {
        [$session, $provider, $request, $cookie, $timebox] = $this->getMocks();
        $guard = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['login'])->setConstructorArgs(['default', $provider, $session, $request, $timebox])->getMock();
        $guard->setDispatcher($events = m::mock(Dispatcher::class));
        $timebox->shouldReceive('call')->once()->andReturnUsing(function ($callback, $microseconds) use ($timebox) {
            return $callback($timebox->shouldReceive('returnEarly')->once()->getMock());
        });
        $events->shouldReceive('dispatch')->once()->with(m::type(Attempting::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(Validated::class));
        $user = $this->createMock(Authenticatable::class);
        $guard->getProvider()->shouldReceive('retrieveByCredentials')->once()->andReturn($user);
        $guard->getProvider()->shouldReceive('validateCredentials')->with($user, ['foo'])->andReturn(true);
        $guard->expects($this->once())->method('login')->with($this->equalTo($user));
        $this->assertTrue($guard->attempt(['foo']));
    }

    public function testAttemptReturnsFalseIfUserNotGiven()
    {
        $mock = $this->getGuard();
        $mock->setDispatcher($events = m::mock(Dispatcher::class));
        $timebox = $mock->getTimebox();
        $timebox->shouldReceive('call')->once()->andReturnUsing(function ($callback, $microseconds) use ($timebox) {
            return $callback($timebox);
        });
        $events->shouldReceive('dispatch')->once()->with(m::type(Attempting::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(Failed::class));
        $events->shouldNotReceive('dispatch')->with(m::type(Validated::class));
        $mock->getProvider()->shouldReceive('retrieveByCredentials')->once()->andReturn(null);
        $this->assertFalse($mock->attempt(['foo']));
    }

    public function testAttemptAndWithCallbacks()
    {
        [$session, $provider, $request, $cookie, $timebox] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName'])->setConstructorArgs(['default', $provider, $session, $request, $timebox])->getMock();
        $mock->setDispatcher($events = m::mock(Dispatcher::class));
        $timebox->shouldReceive('call')->andReturnUsing(function ($callback) use ($timebox) {
            return $callback($timebox->shouldReceive('returnEarly')->getMock());
        });
        $user = m::mock(Authenticatable::class);
        $events->shouldReceive('dispatch')->times(3)->with(m::type(Attempting::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(Login::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(Authenticated::class));
        $events->shouldReceive('dispatch')->twice()->with(m::type(Validated::class));
        $events->shouldReceive('dispatch')->twice()->with(m::type(Failed::class));
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $user->shouldReceive('getAuthIdentifier')->once()->andReturn('bar');
        $mock->getSession()->shouldReceive('put')->with('foo', 'bar')->once();
        $session->shouldReceive('migrate')->once();
        $mock->getProvider()->shouldReceive('retrieveByCredentials')->times(3)->with(['foo'])->andReturn($user);
        $mock->getProvider()->shouldReceive('validateCredentials')->twice()->andReturnTrue();
        $mock->getProvider()->shouldReceive('validateCredentials')->once()->andReturnFalse();

        $this->assertTrue($mock->attemptWhen(['foo'], function ($user, $guard) {
            static::assertInstanceOf(Authenticatable::class, $user);
            static::assertInstanceOf(SessionGuard::class, $guard);

            return true;
        }));

        $this->assertFalse($mock->attemptWhen(['foo'], function ($user, $guard) {
            static::assertInstanceOf(Authenticatable::class, $user);
            static::assertInstanceOf(SessionGuard::class, $guard);

            return false;
        }));

        $executed = false;

        $this->assertFalse($mock->attemptWhen(['foo'], false, function () use (&$executed) {
            return $executed = true;
        }));

        $this->assertFalse($executed);
    }

    public function testLoginStoresIdentifierInSession()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $user = m::mock(Authenticatable::class);
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $user->shouldReceive('getAuthIdentifier')->once()->andReturn('bar');
        $mock->getSession()->shouldReceive('put')->with('foo', 'bar')->once();
        $session->shouldReceive('migrate')->once();
        $mock->login($user);
    }

    public function testSessionGuardIsMacroable()
    {
        $guard = $this->getGuard();

        $guard->macro('foo', function () {
            return 'bar';
        });

        $this->assertSame(
            'bar', $guard->foo()
        );
    }

    public function testLoginFiresLoginAndAuthenticatedEvents()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $mock->setDispatcher($events = m::mock(Dispatcher::class));
        $user = m::mock(Authenticatable::class);
        $events->shouldReceive('dispatch')->once()->with(m::type(Login::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(Authenticated::class));
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $user->shouldReceive('getAuthIdentifier')->once()->andReturn('bar');
        $mock->getSession()->shouldReceive('put')->with('foo', 'bar')->once();
        $session->shouldReceive('migrate')->once();
        $mock->login($user);
    }

    public function testFailedAttemptFiresFailedEvent()
    {
        $guard = $this->getGuard();
        $guard->setDispatcher($events = m::mock(Dispatcher::class));
        $timebox = $guard->getTimebox();
        $timebox->shouldReceive('call')->once()->andReturnUsing(function ($callback, $microseconds) use ($timebox) {
            return $callback($timebox);
        });
        $events->shouldReceive('dispatch')->once()->with(m::type(Attempting::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(Failed::class));
        $events->shouldNotReceive('dispatch')->with(m::type(Validated::class));
        $guard->getProvider()->shouldReceive('retrieveByCredentials')->once()->with(['foo'])->andReturn(null);
        $guard->attempt(['foo']);
    }

    public function testAuthenticateReturnsUserWhenUserIsNotNull()
    {
        $user = m::mock(Authenticatable::class);
        $guard = $this->getGuard()->setUser($user);

        $this->assertEquals($user, $guard->authenticate());
    }

    public function testSetUserFiresAuthenticatedEvent()
    {
        $user = m::mock(Authenticatable::class);
        $guard = $this->getGuard();
        $guard->setDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(Authenticated::class));
        $guard->setUser($user);
    }

    public function testAuthenticateThrowsWhenUserIsNull()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        $guard = $this->getGuard();
        $guard->getSession()->shouldReceive('get')->once()->andReturn(null);

        $guard->authenticate();
    }

    public function testHasUserReturnsTrueWhenUserIsNotNull()
    {
        $user = m::mock(Authenticatable::class);
        $guard = $this->getGuard()->setUser($user);

        $this->assertTrue($guard->hasUser());
    }

    public function testHasUserReturnsFalseWhenUserIsNull()
    {
        $guard = $this->getGuard();
        $guard->getSession()->shouldNotReceive('get');

        $this->assertFalse($guard->hasUser());
    }

    public function testIsAuthedReturnsTrueWhenUserIsNotNull()
    {
        $user = m::mock(Authenticatable::class);
        $mock = $this->getGuard();
        $mock->setUser($user);
        $this->assertTrue($mock->check());
        $this->assertFalse($mock->guest());
    }

    public function testIsAuthedReturnsFalseWhenUserIsNull()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['user'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $mock->expects($this->exactly(2))->method('user')->willReturn(null);
        $this->assertFalse($mock->check());
        $this->assertTrue($mock->guest());
    }

    public function testUserMethodReturnsCachedUser()
    {
        $user = m::mock(Authenticatable::class);
        $mock = $this->getGuard();
        $mock->setUser($user);
        $this->assertSame($user, $mock->user());
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
        $user = m::mock(Authenticatable::class);
        $mock->getProvider()->shouldReceive('retrieveById')->once()->with(1)->andReturn($user);
        $this->assertSame($user, $mock->user());
        $this->assertSame($user, $mock->getUser());
    }

    public function testLogoutRemovesSessionTokenAndRememberMeCookie()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName', 'getRecallerName', 'recaller'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $mock->setCookieJar($cookies = m::mock(CookieJar::class));
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getRememberToken')->once()->andReturn('a');
        $user->shouldReceive('setRememberToken')->once();
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $mock->expects($this->exactly(2))->method('getRecallerName')->willReturn($recallerName = 'bar');
        $mock->expects($this->once())->method('recaller')->willReturn('non-null-cookie');
        $provider->shouldReceive('updateRememberToken')->once();

        $cookie = m::mock(Cookie::class);
        $cookies->shouldReceive('forget')->once()->with('bar')->andReturn($cookie);
        $cookies->shouldReceive('queue')->once()->with($cookie);
        $cookies->shouldReceive('unqueue')->once()->with($recallerName);
        $mock->getSession()->shouldReceive('remove')->once()->with('foo');
        $mock->setUser($user);
        $mock->logout();
        $this->assertNull($mock->getUser());
    }

    public function testLogoutDoesNotEnqueueRememberMeCookieForDeletionIfCookieDoesntExist()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName', 'getRecallerName', 'recaller'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $mock->setCookieJar($cookies = m::mock(CookieJar::class));
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getRememberToken')->andReturn(null);
        $mock->expects($this->once())->method('getRecallerName')->willReturn($recallerName = 'bar');
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $mock->expects($this->once())->method('recaller')->willReturn(null);

        $cookies->shouldReceive('unqueue')->with($recallerName);

        $mock->getSession()->shouldReceive('remove')->once()->with('foo');
        $mock->setUser($user);
        $mock->logout();
        $this->assertNull($mock->getUser());
    }

    public function testLogoutFiresLogoutEvent()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['clearUserDataFromStorage'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $mock->expects($this->once())->method('clearUserDataFromStorage');
        $mock->setDispatcher($events = m::mock(Dispatcher::class));
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getRememberToken')->andReturn(null);
        $events->shouldReceive('dispatch')->once()->with(m::type(Authenticated::class));
        $mock->setUser($user);
        $events->shouldReceive('dispatch')->once()->with(m::type(Logout::class));
        $mock->logout();
    }

    public function testLogoutDoesNotSetRememberTokenIfNotPreviouslySet()
    {
        [$session, $provider, $request] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['clearUserDataFromStorage'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $user = m::mock(Authenticatable::class);

        $user->shouldReceive('getRememberToken')->andReturn(null);
        $user->shouldNotReceive('setRememberToken');
        $provider->shouldNotReceive('updateRememberToken');

        $mock->setUser($user);
        $mock->logout();
    }

    public function testLogoutCurrentDeviceRemovesRememberMeCookie()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName', 'getRecallerName', 'recaller'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $mock->setCookieJar($cookies = m::mock(CookieJar::class));
        $user = m::mock(Authenticatable::class);
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $mock->expects($this->exactly(2))->method('getRecallerName')->willReturn($recallerName = 'bar');
        $mock->expects($this->once())->method('recaller')->willReturn('non-null-cookie');

        $cookie = m::mock(Cookie::class);
        $cookies->shouldReceive('forget')->once()->with('bar')->andReturn($cookie);
        $cookies->shouldReceive('queue')->once()->with($cookie);
        $cookies->shouldReceive('unqueue')->once()->with($recallerName);
        $mock->getSession()->shouldReceive('remove')->once()->with('foo');
        $mock->setUser($user);
        $mock->logoutCurrentDevice();
        $this->assertNull($mock->getUser());
    }

    public function testLogoutCurrentDeviceDoesNotEnqueueRememberMeCookieForDeletionIfCookieDoesntExist()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName', 'getRecallerName', 'recaller'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $mock->setCookieJar($cookies = m::mock(CookieJar::class));
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getRememberToken')->andReturn(null);
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $mock->expects($this->once())->method('getRecallerName')->willReturn($recallerName = 'bar');
        $mock->expects($this->once())->method('recaller')->willReturn(null);
        $cookies->shouldReceive('unqueue')->once()->with($recallerName);

        $mock->getSession()->shouldReceive('remove')->once()->with('foo');
        $mock->setUser($user);
        $mock->logoutCurrentDevice();
        $this->assertNull($mock->getUser());
    }

    public function testLogoutCurrentDeviceFiresLogoutEvent()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['clearUserDataFromStorage'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $mock->expects($this->once())->method('clearUserDataFromStorage');
        $mock->setDispatcher($events = m::mock(Dispatcher::class));
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getRememberToken')->andReturn(null);
        $events->shouldReceive('dispatch')->once()->with(m::type(Authenticated::class));
        $mock->setUser($user);
        $events->shouldReceive('dispatch')->once()->with(m::type(CurrentDeviceLogout::class));
        $mock->logoutCurrentDevice();
    }

    public function testLoginMethodQueuesCookieWhenRemembering()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = new SessionGuard('default', $provider, $session, $request);
        $guard->setCookieJar($cookie);
        $foreverCookie = new Cookie($guard->getRecallerName(), 'foo');
        $cookie->shouldReceive('make')->once()->with($guard->getRecallerName(), 'foo|recaller|bar', 576000)->andReturn($foreverCookie);
        $cookie->shouldReceive('queue')->once()->with($foreverCookie);
        $guard->getSession()->shouldReceive('put')->once()->with($guard->getName(), 'foo');
        $session->shouldReceive('migrate')->once();
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn('foo');
        $user->shouldReceive('getAuthPassword')->andReturn('bar');
        $user->shouldReceive('getRememberToken')->andReturn('recaller');
        $user->shouldReceive('setRememberToken')->never();
        $provider->shouldReceive('updateRememberToken')->never();
        $guard->login($user, true);
    }

    public function testLoginMethodQueuesCookieWhenRememberingAndAllowsOverride()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = new SessionGuard('default', $provider, $session, $request);
        $guard->setRememberDuration(5000);
        $guard->setCookieJar($cookie);
        $foreverCookie = new Cookie($guard->getRecallerName(), 'foo');
        $cookie->shouldReceive('make')->once()->with($guard->getRecallerName(), 'foo|recaller|bar', 5000)->andReturn($foreverCookie);
        $cookie->shouldReceive('queue')->once()->with($foreverCookie);
        $guard->getSession()->shouldReceive('put')->once()->with($guard->getName(), 'foo');
        $session->shouldReceive('migrate')->once();
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn('foo');
        $user->shouldReceive('getAuthPassword')->andReturn('bar');
        $user->shouldReceive('getRememberToken')->andReturn('recaller');
        $user->shouldReceive('setRememberToken')->never();
        $provider->shouldReceive('updateRememberToken')->never();
        $guard->login($user, true);
    }

    public function testLoginMethodCreatesRememberTokenIfOneDoesntExist()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = new SessionGuard('default', $provider, $session, $request);
        $guard->setCookieJar($cookie);
        $foreverCookie = new Cookie($guard->getRecallerName(), 'foo');
        $cookie->shouldReceive('make')->once()->andReturn($foreverCookie);
        $cookie->shouldReceive('queue')->once()->with($foreverCookie);
        $guard->getSession()->shouldReceive('put')->once()->with($guard->getName(), 'foo');
        $session->shouldReceive('migrate')->once();
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn('foo');
        $user->shouldReceive('getAuthPassword')->andReturn('foo');
        $user->shouldReceive('getRememberToken')->andReturn(null);
        $user->shouldReceive('setRememberToken')->once();
        $provider->shouldReceive('updateRememberToken')->once();
        $guard->login($user, true);
    }

    public function testLoginUsingIdLogsInWithUser()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();

        $guard = m::mock(SessionGuard::class, ['default', $provider, $session])->makePartial();

        $user = m::mock(Authenticatable::class);
        $guard->getProvider()->shouldReceive('retrieveById')->once()->with(10)->andReturn($user);
        $guard->shouldReceive('login')->once()->with($user, false);

        $this->assertSame($user, $guard->loginUsingId(10));
    }

    public function testLoginUsingIdFailure()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = m::mock(SessionGuard::class, ['default', $provider, $session])->makePartial();

        $guard->getProvider()->shouldReceive('retrieveById')->once()->with(11)->andReturn(null);
        $guard->shouldNotReceive('login');

        $this->assertFalse($guard->loginUsingId(11));
    }

    public function testOnceUsingIdSetsUser()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = m::mock(SessionGuard::class, ['default', $provider, $session])->makePartial();

        $user = m::mock(Authenticatable::class);
        $guard->getProvider()->shouldReceive('retrieveById')->once()->with(10)->andReturn($user);
        $guard->shouldReceive('setUser')->once()->with($user);

        $this->assertSame($user, $guard->onceUsingId(10));
    }

    public function testOnceUsingIdFailure()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = m::mock(SessionGuard::class, ['default', $provider, $session])->makePartial();

        $guard->getProvider()->shouldReceive('retrieveById')->once()->with(11)->andReturn(null);
        $guard->shouldNotReceive('setUser');

        $this->assertFalse($guard->onceUsingId(11));
    }

    public function testUserUsesRememberCookieIfItExists()
    {
        $guard = $this->getGuard();
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $request = Request::create('/', 'GET', [], [$guard->getRecallerName() => 'id|recaller|baz']);
        $guard = new SessionGuard('default', $provider, $session, $request);
        $guard->getSession()->shouldReceive('get')->once()->with($guard->getName())->andReturn(null);
        $user = m::mock(Authenticatable::class);
        $guard->getProvider()->shouldReceive('retrieveByToken')->once()->with('id', 'recaller')->andReturn($user);
        $user->shouldReceive('getAuthIdentifier')->once()->andReturn('bar');
        $guard->getSession()->shouldReceive('put')->with($guard->getName(), 'bar')->once();
        $session->shouldReceive('migrate')->once();
        $this->assertSame($user, $guard->user());
        $this->assertTrue($guard->viaRemember());
    }

    public function testLoginOnceSetsUser()
    {
        [$session, $provider, $request, $cookie, $timebox] = $this->getMocks();
        $guard = m::mock(SessionGuard::class, ['default', $provider, $session, $request, $timebox])->makePartial();
        $user = m::mock(Authenticatable::class);
        $timebox->shouldReceive('call')->once()->andReturnUsing(function ($callback) use ($timebox) {
            return $callback($timebox->shouldReceive('returnEarly')->once()->getMock());
        });
        $guard->getProvider()->shouldReceive('retrieveByCredentials')->once()->with(['foo'])->andReturn($user);
        $guard->getProvider()->shouldReceive('validateCredentials')->once()->with($user, ['foo'])->andReturn(true);
        $guard->shouldReceive('setUser')->once()->with($user);
        $this->assertTrue($guard->once(['foo']));
    }

    public function testLoginOnceFailure()
    {
        [$session, $provider, $request, $cookie, $timebox] = $this->getMocks();
        $guard = m::mock(SessionGuard::class, ['default', $provider, $session, $request, $timebox])->makePartial();
        $user = m::mock(Authenticatable::class);
        $timebox->shouldReceive('call')->once()->andReturnUsing(function ($callback) use ($timebox) {
            return $callback($timebox);
        });
        $guard->getProvider()->shouldReceive('retrieveByCredentials')->once()->with(['foo'])->andReturn($user);
        $guard->getProvider()->shouldReceive('validateCredentials')->once()->with($user, ['foo'])->andReturn(false);
        $this->assertFalse($guard->once(['foo']));
    }

    public function testForgetUserSetsUserToNull()
    {
        $user = m::mock(Authenticatable::class);
        $guard = $this->getGuard()->setUser($user);
        $guard->forgetUser();
        $this->assertNull($guard->getUser());
    }

    protected function getGuard()
    {
        [$session, $provider, $request, $cookie, $timebox] = $this->getMocks();

        return new SessionGuard('default', $provider, $session, $request, $timebox);
    }

    protected function getMocks()
    {
        return [
            m::mock(Session::class),
            m::mock(UserProvider::class),
            Request::create('/', 'GET'),
            m::mock(CookieJar::class),
            m::mock(Timebox::class),
        ];
    }

    protected function getCookieJar()
    {
        return new CookieJar(Request::create('/foo', 'GET'), m::mock(Encrypter::class), ['domain' => 'foo.com', 'path' => '/', 'secure' => false, 'httpOnly' => false]);
    }
}
