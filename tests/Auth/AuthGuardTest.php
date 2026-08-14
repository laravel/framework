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
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthGuardTest extends TestCase
{
    public function testBasicReturnsNullOnValidAttempt()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = Mockery::mock(SessionGuard::class.'[check,attempt]', ['default', $provider, $session]);
        $guard->expects('check')->andReturn(false);
        $guard->expects('attempt')->with(['email' => 'foo@bar.com', 'password' => 'secret'])->andReturn(true);
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);

        $guard->basic('email');
    }

    public function testBasicReturnsNullWhenAlreadyLoggedIn()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = Mockery::mock(SessionGuard::class.'[check]', ['default', $provider, $session]);
        $guard->expects('check')->andReturn(true);
        $guard->shouldReceive('attempt')->never();
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);

        $guard->basic('email');
    }

    public function testBasicReturnsResponseOnFailure()
    {
        $this->expectException(UnauthorizedHttpException::class);

        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = Mockery::mock(SessionGuard::class.'[check,attempt]', ['default', $provider, $session]);
        $guard->expects('check')->andReturn(false);
        $guard->expects('attempt')->with(['email' => 'foo@bar.com', 'password' => 'secret'])->andReturn(false);
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);
        $guard->basic('email');
    }

    public function testBasicWithExtraConditions()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = Mockery::mock(SessionGuard::class.'[check,attempt]', ['default', $provider, $session]);
        $guard->expects('check')->andReturn(false);
        $guard->expects('attempt')->with(['email' => 'foo@bar.com', 'password' => 'secret', 'active' => 1])->andReturn(true);
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);

        $guard->basic('email', ['active' => 1]);
    }

    public function testBasicWithExtraArrayConditions()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = Mockery::mock(SessionGuard::class.'[check,attempt]', ['default', $provider, $session]);
        $guard->expects('check')->andReturn(false);
        $guard->expects('attempt')->with(['email' => 'foo@bar.com', 'password' => 'secret', 'active' => 1, 'type' => [1, 2, 3]])->andReturn(true);
        $request = Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']);
        $guard->setRequest($request);

        $guard->basic('email', ['active' => 1, 'type' => [1, 2, 3]]);
    }

    public function testAttemptCallsRetrieveByCredentials()
    {
        $guard = $this->getGuard();
        $events = Mockery::mock(Dispatcher::class);
        $guard->setDispatcher($events);
        $timebox = $guard->getTimebox();
        $timebox->expects('call')->andReturnUsing(function ($callback) use ($timebox) {
            return $callback($timebox);
        });
        $events->expects('dispatch')->with(Mockery::type(Attempting::class));
        $events->expects('dispatch')->with(Mockery::type(Failed::class));
        $events->shouldNotReceive('dispatch')->with(Mockery::type(Validated::class));
        $guard->getProvider()->expects('retrieveByCredentials')->with(['foo']);
        $guard->getProvider()->shouldNotReceive('rehashPasswordIfRequired');
        $guard->attempt(['foo']);
    }

    public function testAttemptReturnsUserInterface()
    {
        [$session, $provider, $request, $cookie, $timebox] = $this->getMocks();
        $guard = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['login'])->setConstructorArgs(['default', $provider, $session, $request, $timebox])->getMock();
        $events = Mockery::mock(Dispatcher::class);
        $guard->setDispatcher($events);
        $timebox->expects('call')->andReturnUsing(function ($callback, $microseconds) use ($timebox) {
            return $callback($timebox->expects('returnEarly')->getMock());
        });
        $events->expects('dispatch')->with(Mockery::type(Attempting::class));
        $events->expects('dispatch')->with(Mockery::type(Validated::class));
        $user = $this->createStub(Authenticatable::class);
        $guard->getProvider()->expects('retrieveByCredentials')->andReturn($user);
        $guard->getProvider()->expects('validateCredentials')->with($user, ['foo'])->andReturn(true);
        $guard->getProvider()->expects('rehashPasswordIfRequired')->with($user, ['foo']);
        $guard->expects($this->once())->method('login')->with($user);
        $this->assertTrue($guard->attempt(['foo']));
    }

    public function testAttemptReturnsFalseIfUserNotGiven()
    {
        $mock = $this->getGuard();
        $events = Mockery::mock(Dispatcher::class);
        $mock->setDispatcher($events);
        $timebox = $mock->getTimebox();
        $timebox->expects('call')->andReturnUsing(function ($callback, $microseconds) use ($timebox) {
            return $callback($timebox);
        });
        $events->expects('dispatch')->with(Mockery::type(Attempting::class));
        $events->expects('dispatch')->with(Mockery::type(Failed::class));
        $events->shouldNotReceive('dispatch')->with(Mockery::type(Validated::class));
        $mock->getProvider()->expects('retrieveByCredentials')->andReturn(null);
        $mock->getProvider()->shouldNotReceive('rehashPasswordIfRequired');
        $this->assertFalse($mock->attempt(['foo']));
    }

    public function testAttemptAndWithCallbacks()
    {
        [$session, $provider, $request, $cookie, $timebox] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName'])->setConstructorArgs(['default', $provider, $session, $request, $timebox])->getMock();
        $events = Mockery::mock(Dispatcher::class);
        $mock->setDispatcher($events);
        $timebox->shouldReceive('call')->andReturnUsing(function ($callback) use ($timebox) {
            return $callback($timebox->shouldReceive('returnEarly')->getMock());
        });
        $user = Mockery::mock(Authenticatable::class);
        $events->expects('dispatch')->times(3)->with(Mockery::type(Attempting::class));
        $events->expects('dispatch')->with(Mockery::type(Login::class));
        $events->expects('dispatch')->with(Mockery::type(Authenticated::class));
        $events->expects('dispatch')->times(2)->with(Mockery::type(Validated::class));
        $events->expects('dispatch')->times(2)->with(Mockery::type(Failed::class));
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $user->expects('getAuthIdentifier')->andReturn('bar');
        $mock->getSession()->expects('put')->with('foo', 'bar');
        $session->expects('regenerate');
        $mock->getProvider()->expects('retrieveByCredentials')->times(3)->with(['foo'])->andReturn($user);
        $mock->getProvider()->expects('validateCredentials')->times(2)->andReturnTrue();
        $mock->getProvider()->expects('validateCredentials')->andReturnFalse();
        $mock->getProvider()->expects('rehashPasswordIfRequired')->with($user, ['foo']);

        $this->assertTrue($mock->attemptWhen(['foo'], function ($user, $guard) {
            $this->assertInstanceOf(Authenticatable::class, $user);
            $this->assertInstanceOf(SessionGuard::class, $guard);

            return true;
        }));

        $this->assertFalse($mock->attemptWhen(['foo'], function ($user, $guard) {
            $this->assertInstanceOf(Authenticatable::class, $user);
            $this->assertInstanceOf(SessionGuard::class, $guard);

            return false;
        }));

        $executed = false;

        $this->assertFalse($mock->attemptWhen(['foo'], false, function () use (&$executed) {
            return $executed = true;
        }));

        $this->assertFalse($executed);
    }

    public function testAttemptRehashesPasswordWhenRequired()
    {
        [$session, $provider, $request, $cookie, $timebox] = $this->getMocks();
        $guard = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['login'])->setConstructorArgs(['default', $provider, $session, $request, $timebox])->getMock();
        $events = Mockery::mock(Dispatcher::class);
        $guard->setDispatcher($events);
        $timebox->expects('call')->andReturnUsing(function ($callback, $microseconds) use ($timebox) {
            return $callback($timebox->expects('returnEarly')->getMock());
        });
        $events->expects('dispatch')->with(Mockery::type(Attempting::class));
        $events->expects('dispatch')->with(Mockery::type(Validated::class));
        $user = $this->createStub(Authenticatable::class);
        $guard->getProvider()->expects('retrieveByCredentials')->andReturn($user);
        $guard->getProvider()->expects('validateCredentials')->with($user, ['foo'])->andReturn(true);
        $guard->getProvider()->expects('rehashPasswordIfRequired')->with($user, ['foo']);
        $guard->expects($this->once())->method('login')->with($user);
        $this->assertTrue($guard->attempt(['foo']));
    }

    public function testAttemptDoesntRehashPasswordWhenDisabled()
    {
        [$session, $provider, $request, $cookie, $timebox] = $this->getMocks();
        $guard = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['login'])
            ->setConstructorArgs(['default', $provider, $session, $request, $timebox, $rehashOnLogin = false])
            ->getMock();
        $events = Mockery::mock(Dispatcher::class);
        $guard->setDispatcher($events);
        $timebox->expects('call')->andReturnUsing(function ($callback, $microseconds) use ($timebox) {
            return $callback($timebox->expects('returnEarly')->getMock());
        });
        $events->expects('dispatch')->with(Mockery::type(Attempting::class));
        $events->expects('dispatch')->with(Mockery::type(Validated::class));
        $user = $this->createStub(Authenticatable::class);
        $guard->getProvider()->expects('retrieveByCredentials')->andReturn($user);
        $guard->getProvider()->expects('validateCredentials')->with($user, ['foo'])->andReturn(true);
        $guard->getProvider()->shouldNotReceive('rehashPasswordIfRequired');
        $guard->expects($this->once())->method('login')->with($user);
        $this->assertTrue($guard->attempt(['foo']));
    }

    public function testLoginStoresIdentifierInSession()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $user = Mockery::mock(Authenticatable::class);
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $user->expects('getAuthIdentifier')->andReturn('bar');
        $mock->getSession()->expects('put')->with('foo', 'bar');
        $session->expects('regenerate');
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
        $events = Mockery::mock(Dispatcher::class);
        $mock->setDispatcher($events);
        $user = Mockery::mock(Authenticatable::class);
        $events->expects('dispatch')->with(Mockery::type(Login::class));
        $events->expects('dispatch')->with(Mockery::type(Authenticated::class));
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $user->expects('getAuthIdentifier')->andReturn('bar');
        $mock->getSession()->expects('put')->with('foo', 'bar');
        $session->expects('regenerate');
        $mock->login($user);
    }

    public function testFailedAttemptFiresFailedEvent()
    {
        $guard = $this->getGuard();
        $events = Mockery::mock(Dispatcher::class);
        $guard->setDispatcher($events);
        $timebox = $guard->getTimebox();
        $timebox->expects('call')->andReturnUsing(function ($callback, $microseconds) use ($timebox) {
            return $callback($timebox);
        });
        $events->expects('dispatch')->with(Mockery::type(Attempting::class));
        $events->expects('dispatch')->with(Mockery::type(Failed::class));
        $events->shouldNotReceive('dispatch')->with(Mockery::type(Validated::class));
        $guard->getProvider()->expects('retrieveByCredentials')->with(['foo'])->andReturn(null);
        $guard->getProvider()->shouldNotReceive('rehashPasswordIfRequired');
        $guard->attempt(['foo']);
    }

    public function testAuthenticateReturnsUserWhenUserIsNotNull()
    {
        $user = Mockery::mock(Authenticatable::class);
        $guard = $this->getGuard();
        $guard->setUser($user);

        $this->assertEquals($user, $guard->authenticate());
    }

    public function testSetUserFiresAuthenticatedEvent()
    {
        $user = Mockery::mock(Authenticatable::class);
        $guard = $this->getGuard();
        $events = Mockery::mock(Dispatcher::class);
        $events->expects('dispatch')->with(Mockery::type(Authenticated::class));
        $guard->setDispatcher($events);
        $guard->setUser($user);
    }

    public function testAuthenticateThrowsWhenUserIsNull()
    {
        $this->expectExceptionObject(new AuthenticationException('Unauthenticated.'));

        $guard = $this->getGuard();
        $guard->getSession()->expects('get')->andReturn(null);

        $guard->authenticate();
    }

    public function testHasUserReturnsTrueWhenUserIsNotNull()
    {
        $user = Mockery::mock(Authenticatable::class);
        $guard = $this->getGuard();
        $guard->setUser($user);

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
        $user = Mockery::mock(Authenticatable::class);
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
        $user = Mockery::mock(Authenticatable::class);
        $mock = $this->getGuard();
        $mock->setUser($user);
        $this->assertSame($user, $mock->user());
    }

    public function testNullIsReturnedForUserIfNoUserFound()
    {
        $mock = $this->getGuard();
        $mock->getSession()->expects('get')->andReturn(null);
        $this->assertNull($mock->user());
    }

    public function testUserIsSetToRetrievedUser()
    {
        $mock = $this->getGuard();
        $mock->getSession()->expects('get')->andReturn(1);
        $user = Mockery::mock(Authenticatable::class);
        $mock->getProvider()->expects('retrieveById')->with(1)->andReturn($user);
        $this->assertSame($user, $mock->user());
        $this->assertSame($user, $mock->getUser());
    }

    public function testLogoutRemovesSessionTokenAndRememberMeCookie()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName', 'getRecallerName', 'recaller'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $cookies = Mockery::mock(CookieJar::class);
        $mock->setCookieJar($cookies);
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getRememberToken')->andReturn('a');
        $user->expects('setRememberToken');
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $mock->expects($this->exactly(2))->method('getRecallerName')->willReturn($recallerName = 'bar');
        $mock->expects($this->once())->method('recaller')->willReturn('non-null-cookie');
        $provider->expects('updateRememberToken');

        $cookie = Mockery::mock(Cookie::class);
        $cookies->expects('forget')->with('bar')->andReturn($cookie);
        $cookies->expects('queue')->with($cookie);
        $cookies->expects('unqueue')->with($recallerName);
        $mock->getSession()->expects('remove')->with('foo');
        $mock->setUser($user);
        $mock->logout();
        $this->assertNull($mock->getUser());
    }

    public function testLogoutDoesNotEnqueueRememberMeCookieForDeletionIfCookieDoesntExist()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName', 'getRecallerName', 'recaller'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $cookies = Mockery::mock(CookieJar::class);
        $mock->setCookieJar($cookies);
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getRememberToken')->andReturn(null);
        $mock->expects($this->once())->method('getRecallerName')->willReturn($recallerName = 'bar');
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $mock->expects($this->once())->method('recaller')->willReturn(null);

        $cookies->expects('unqueue')->with($recallerName);

        $mock->getSession()->expects('remove')->with('foo');
        $mock->setUser($user);
        $mock->logout();
        $this->assertNull($mock->getUser());
    }

    public function testLogoutFiresLogoutEvent()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['clearUserDataFromStorage'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $mock->expects($this->once())->method('clearUserDataFromStorage');
        $events = Mockery::mock(Dispatcher::class);
        $mock->setDispatcher($events);
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getRememberToken')->andReturn(null);
        $events->expects('dispatch')->with(Mockery::type(Authenticated::class));
        $mock->setUser($user);
        $events->expects('dispatch')->with(Mockery::type(Logout::class));
        $mock->logout();
    }

    public function testLogoutDoesNotSetRememberTokenIfNotPreviouslySet()
    {
        [$session, $provider, $request] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['clearUserDataFromStorage'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $user = Mockery::mock(Authenticatable::class);

        $user->expects('getRememberToken')->andReturn(null);
        $user->shouldNotReceive('setRememberToken');
        $provider->shouldNotReceive('updateRememberToken');

        $mock->setUser($user);
        $mock->logout();
    }

    public function testLogoutCurrentDeviceRemovesRememberMeCookie()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName', 'getRecallerName', 'recaller'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $cookies = Mockery::mock(CookieJar::class);
        $mock->setCookieJar($cookies);
        $user = Mockery::mock(Authenticatable::class);
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $mock->expects($this->exactly(2))->method('getRecallerName')->willReturn($recallerName = 'bar');
        $mock->expects($this->once())->method('recaller')->willReturn('non-null-cookie');

        $cookie = Mockery::mock(Cookie::class);
        $cookies->expects('forget')->with('bar')->andReturn($cookie);
        $cookies->expects('queue')->with($cookie);
        $cookies->expects('unqueue')->with($recallerName);
        $mock->getSession()->expects('remove')->with('foo');
        $mock->setUser($user);
        $mock->logoutCurrentDevice();
        $this->assertNull($mock->getUser());
    }

    public function testLogoutCurrentDeviceDoesNotEnqueueRememberMeCookieForDeletionIfCookieDoesntExist()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['getName', 'getRecallerName', 'recaller'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $cookies = Mockery::mock(CookieJar::class);
        $mock->setCookieJar($cookies);
        $user = Mockery::mock(Authenticatable::class);
        $mock->expects($this->once())->method('getName')->willReturn('foo');
        $mock->expects($this->once())->method('getRecallerName')->willReturn($recallerName = 'bar');
        $mock->expects($this->once())->method('recaller')->willReturn(null);
        $cookies->expects('unqueue')->with($recallerName);

        $mock->getSession()->expects('remove')->with('foo');
        $mock->setUser($user);
        $mock->logoutCurrentDevice();
        $this->assertNull($mock->getUser());
    }

    public function testLogoutCurrentDeviceFiresLogoutEvent()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $mock = $this->getMockBuilder(SessionGuard::class)->onlyMethods(['clearUserDataFromStorage'])->setConstructorArgs(['default', $provider, $session, $request])->getMock();
        $mock->expects($this->once())->method('clearUserDataFromStorage');
        $events = Mockery::mock(Dispatcher::class);
        $mock->setDispatcher($events);
        $user = Mockery::mock(Authenticatable::class);
        $events->expects('dispatch')->with(Mockery::type(Authenticated::class));
        $mock->setUser($user);
        $events->expects('dispatch')->with(Mockery::type(CurrentDeviceLogout::class));
        $mock->logoutCurrentDevice();
    }

    public function testLoginMethodQueuesCookieWhenRemembering()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = new SessionGuard('default', $provider, $session, $request);
        $guard->setCookieJar($cookie);
        $foreverCookie = new Cookie($guard->getRecallerName(), 'foo');
        $expectedHash = hash_hmac('sha256', 'bar', 'base-key-for-password-hash-mac');
        $cookie->expects('make')->with($guard->getRecallerName(), 'foo|recaller|'.$expectedHash, 576000)->andReturn($foreverCookie);
        $cookie->expects('queue')->with($foreverCookie);
        $guard->getSession()->expects('put')->with($guard->getName(), 'foo');
        $session->expects('regenerate');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthIdentifier')->times(2)->andReturn('foo');
        $user->expects('getAuthPassword')->andReturn('bar');
        $user->expects('getRememberToken')->times(2)->andReturn('recaller');
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
        $expectedHash = hash_hmac('sha256', 'bar', 'base-key-for-password-hash-mac');
        $cookie->expects('make')->with($guard->getRecallerName(), 'foo|recaller|'.$expectedHash, 5000)->andReturn($foreverCookie);
        $cookie->expects('queue')->with($foreverCookie);
        $guard->getSession()->expects('put')->with($guard->getName(), 'foo');
        $session->expects('regenerate');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthIdentifier')->times(2)->andReturn('foo');
        $user->expects('getAuthPassword')->andReturn('bar');
        $user->expects('getRememberToken')->times(2)->andReturn('recaller');
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
        $cookie->expects('make')->andReturn($foreverCookie);
        $cookie->expects('queue')->with($foreverCookie);
        $guard->getSession()->expects('put')->with($guard->getName(), 'foo');
        $session->expects('regenerate');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthIdentifier')->times(2)->andReturn('foo');
        $user->expects('getAuthPassword')->andReturn('foo');
        $user->expects('getRememberToken')->times(2)->andReturn(null);
        $user->expects('setRememberToken');
        $provider->expects('updateRememberToken');
        $guard->login($user, true);
    }

    public function testLoginUsingIdLogsInWithUser()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();

        $guard = Mockery::mock(SessionGuard::class, ['default', $provider, $session])->makePartial();

        $user = Mockery::mock(Authenticatable::class);
        $guard->getProvider()->expects('retrieveById')->with(10)->andReturn($user);
        $guard->expects('login')->with($user, false);

        $this->assertSame($user, $guard->loginUsingId(10));
    }

    public function testLoginUsingIdFailure()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = Mockery::mock(SessionGuard::class, ['default', $provider, $session])->makePartial();

        $guard->getProvider()->expects('retrieveById')->with(11)->andReturn(null);
        $guard->shouldNotReceive('login');

        $this->assertFalse($guard->loginUsingId(11));
    }

    public function testOnceUsingIdSetsUser()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = Mockery::mock(SessionGuard::class, ['default', $provider, $session])->makePartial();

        $user = Mockery::mock(Authenticatable::class);
        $guard->getProvider()->expects('retrieveById')->with(10)->andReturn($user);
        $guard->expects('setUser')->with($user);

        $this->assertSame($user, $guard->onceUsingId(10));
    }

    public function testOnceUsingIdFailure()
    {
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $guard = Mockery::mock(SessionGuard::class, ['default', $provider, $session])->makePartial();

        $guard->getProvider()->expects('retrieveById')->with(11)->andReturn(null);
        $guard->shouldNotReceive('setUser');

        $this->assertFalse($guard->onceUsingId(11));
    }

    public function testUserUsesRememberCookieIfItExists()
    {
        $guard = $this->getGuard();
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $request = Request::create('/', 'GET', [], [$guard->getRecallerName() => 'id|recaller|baz']);
        $guard = new SessionGuard('default', $provider, $session, $request);
        $guard->getSession()->expects('get')->with($guard->getName())->andReturn(null);
        $user = Mockery::mock(Authenticatable::class);
        $guard->getProvider()->expects('retrieveByToken')->with('id', 'recaller')->andReturn($user);
        $user->expects('getAuthIdentifier')->andReturn('bar');
        $guard->getSession()->expects('put')->with($guard->getName(), 'bar');
        $session->expects('regenerate');
        $this->assertSame($user, $guard->user());
        $this->assertTrue($guard->viaRemember());
    }

    public function testLoginOnceSetsUser()
    {
        [$session, $provider, $request, $cookie, $timebox] = $this->getMocks();
        $guard = Mockery::mock(SessionGuard::class, ['default', $provider, $session, $request, $timebox])->makePartial();
        $user = Mockery::mock(Authenticatable::class);
        $timebox->expects('call')->andReturnUsing(function ($callback) use ($timebox) {
            return $callback($timebox->expects('returnEarly')->getMock());
        });
        $guard->getProvider()->expects('retrieveByCredentials')->with(['foo'])->andReturn($user);
        $guard->getProvider()->expects('validateCredentials')->with($user, ['foo'])->andReturn(true);
        $guard->getProvider()->expects('rehashPasswordIfRequired')->with($user, ['foo']);
        $guard->expects('setUser')->with($user);
        $this->assertTrue($guard->once(['foo']));
    }

    public function testLoginOnceFailure()
    {
        [$session, $provider, $request, $cookie, $timebox] = $this->getMocks();
        $guard = Mockery::mock(SessionGuard::class, ['default', $provider, $session, $request, $timebox])->makePartial();
        $user = Mockery::mock(Authenticatable::class);
        $timebox->expects('call')->andReturnUsing(function ($callback) use ($timebox) {
            return $callback($timebox);
        });
        $guard->getProvider()->expects('retrieveByCredentials')->with(['foo'])->andReturn($user);
        $guard->getProvider()->expects('validateCredentials')->with($user, ['foo'])->andReturn(false);
        $guard->getProvider()->shouldNotReceive('rehashPasswordIfRequired');
        $this->assertFalse($guard->once(['foo']));
    }

    public function testForgetUserSetsUserToNull()
    {
        $user = Mockery::mock(Authenticatable::class);
        $guard = $this->getGuard();
        $guard->setUser($user);
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
            Mockery::mock(Session::class),
            Mockery::mock(UserProvider::class),
            Request::create('/', 'GET'),
            Mockery::mock(CookieJar::class),
            Mockery::mock(Timebox::class),
        ];
    }

    protected function getCookieJar()
    {
        return new CookieJar(Request::create('/foo', 'GET'), Mockery::mock(Encrypter::class), ['domain' => 'foo.com', 'path' => '/', 'secure' => false, 'httpOnly' => false]);
    }
}
