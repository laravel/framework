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
use Illuminate\Auth\GenericUser;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Session\Session;
use Illuminate\Cookie\CookieJar;
use Illuminate\Events\Dispatcher;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Testing\Fakes\EventFake;
use Illuminate\Support\Timebox;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthGuardTest extends TestCase
{
    use VerifiesDoubles;

    public function testBasicReturnsNullOnValidAttempt()
    {
        [$guard, $provider, $session] = $this->getRealGuard();

        $user = new GenericUser(['id' => 10, 'password' => 'secret']);
        $credentials = ['email' => 'foo@bar.com', 'password' => 'secret'];
        $provider->expects('retrieveByCredentials')->with($credentials)->returns($user);
        $provider->expects('validateCredentials')->with($user, $credentials)->returns(true);
        $provider->expects('rehashPasswordIfRequired')->with($user, $credentials);
        $guard->setRequest(Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']));

        $this->assertNull($guard->basic('email'));
        $this->assertSame($user, $guard->user());
        $this->assertSame(10, $session->get($guard->getName()));
    }

    public function testBasicReturnsNullWhenAlreadyLoggedIn()
    {
        [$guard, $provider] = $this->getRealGuard();

        $user = new GenericUser(['id' => 10, 'password' => 'secret']);
        $guard->setUser($user);
        $provider->expects('retrieveByCredentials')->never();
        $guard->setRequest(Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']));

        $this->assertNull($guard->basic('email'));
        $this->assertSame($user, $guard->user());
    }

    public function testBasicReturnsResponseOnFailure()
    {
        $this->expectException(UnauthorizedHttpException::class);

        [$guard, $provider] = $this->getRealGuard();

        $user = new GenericUser(['id' => 10, 'password' => 'secret']);
        $credentials = ['email' => 'foo@bar.com', 'password' => 'secret'];
        $provider->expects('retrieveByCredentials')->with($credentials)->returns($user);
        $provider->expects('validateCredentials')->with($user, $credentials)->returns(false);
        $provider->expects('rehashPasswordIfRequired')->never();
        $guard->setRequest(Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']));

        $guard->basic('email');
    }

    public function testBasicWithExtraConditions()
    {
        [$guard, $provider, $session] = $this->getRealGuard();

        $user = new GenericUser(['id' => 10, 'password' => 'secret']);
        $credentials = ['email' => 'foo@bar.com', 'password' => 'secret', 'active' => 1];
        $provider->expects('retrieveByCredentials')->with($credentials)->returns($user);
        $provider->expects('validateCredentials')->with($user, $credentials)->returns(true);
        $provider->expects('rehashPasswordIfRequired')->with($user, $credentials);
        $guard->setRequest(Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']));

        $this->assertNull($guard->basic('email', ['active' => 1]));
        $this->assertSame($user, $guard->user());
        $this->assertSame(10, $session->get($guard->getName()));
    }

    public function testBasicWithExtraArrayConditions()
    {
        [$guard, $provider, $session] = $this->getRealGuard();

        $user = new GenericUser(['id' => 10, 'password' => 'secret']);
        $credentials = ['email' => 'foo@bar.com', 'password' => 'secret', 'active' => 1, 'type' => [1, 2, 3]];
        $provider->expects('retrieveByCredentials')->with($credentials)->returns($user);
        $provider->expects('validateCredentials')->with($user, $credentials)->returns(true);
        $provider->expects('rehashPasswordIfRequired')->with($user, $credentials);
        $guard->setRequest(Request::create('/', 'GET', [], [], [], ['PHP_AUTH_USER' => 'foo@bar.com', 'PHP_AUTH_PW' => 'secret']));

        $this->assertNull($guard->basic('email', ['active' => 1, 'type' => [1, 2, 3]]));
        $this->assertSame($user, $guard->user());
        $this->assertSame(10, $session->get($guard->getName()));
    }

    public function testAttemptCallsRetrieveByCredentials()
    {
        $guard = $this->getGuard();
        $events = new EventFake(new Dispatcher);
        $guard->setDispatcher($events);
        $timebox = $guard->getTimebox();
        $timebox->expects('call')->resolves(function ($callback) use ($timebox) {
            return $callback($timebox);
        });
        $guard->getProvider()->expects('retrieveByCredentials')->with(['foo']);
        $guard->getProvider()->expects('rehashPasswordIfRequired')->never();
        $guard->attempt(['foo']);

        $events->assertDispatchedOnce(Attempting::class);
        $events->assertDispatchedOnce(Failed::class);
        $events->assertNotDispatched(Validated::class);
    }

    public function testAttemptReturnsUserInterface()
    {
        [$guard, $provider] = $this->getRealGuard();
        $events = new EventFake(new Dispatcher);
        $guard->setDispatcher($events);
        $user = new GenericUser(['id' => 10]);
        $provider->expects('retrieveByCredentials')->returns($user);
        $provider->expects('validateCredentials')->with($user, ['foo'])->returns(true);
        $provider->expects('rehashPasswordIfRequired')->with($user, ['foo']);
        $this->assertTrue($guard->attempt(['foo']));
        $this->assertSame($user, $guard->getUser());

        $events->assertDispatchedOnce(Attempting::class);
        $events->assertDispatchedOnce(Validated::class);
    }

    public function testAttemptReturnsFalseIfUserNotGiven()
    {
        $mock = $this->getGuard();
        $events = new EventFake(new Dispatcher);
        $mock->setDispatcher($events);
        $timebox = $mock->getTimebox();
        $timebox->expects('call')->resolves(function ($callback, $microseconds) use ($timebox) {
            return $callback($timebox);
        });
        $mock->getProvider()->expects('retrieveByCredentials')->returns(null);
        $mock->getProvider()->expects('rehashPasswordIfRequired')->never();
        $this->assertFalse($mock->attempt(['foo']));

        $events->assertDispatchedOnce(Attempting::class);
        $events->assertDispatchedOnce(Failed::class);
        $events->assertNotDispatched(Validated::class);
    }

    public function testAttemptAndWithCallbacks()
    {
        [$mock, $provider, $session] = $this->getRealGuard();
        $events = new EventFake(new Dispatcher);
        $mock->setDispatcher($events);
        $user = new GenericUser(['id' => 'bar']);
        $provider->expects('retrieveByCredentials')->times(3)->with(['foo'])->returns($user);
        $provider->expects('validateCredentials')->times(3)->returns(true, true, false);
        $provider->expects('rehashPasswordIfRequired')->with($user, ['foo']);

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
        $this->assertSame('bar', $session->get($mock->getName()));

        $events->assertDispatchedTimes(Attempting::class, 3);
        $events->assertDispatchedOnce(Login::class);
        $events->assertDispatchedOnce(Authenticated::class);
        $events->assertDispatchedTimes(Validated::class, 2);
        $events->assertDispatchedTimes(Failed::class, 2);
    }

    public function testAttemptRehashesPasswordWhenRequired()
    {
        [$guard, $provider] = $this->getRealGuard();
        $events = new EventFake(new Dispatcher);
        $guard->setDispatcher($events);
        $user = new GenericUser(['id' => 10]);
        $provider->expects('retrieveByCredentials')->returns($user);
        $provider->expects('validateCredentials')->with($user, ['foo'])->returns(true);
        $provider->expects('rehashPasswordIfRequired')->with($user, ['foo']);
        $this->assertTrue($guard->attempt(['foo']));
        $this->assertSame($user, $guard->getUser());

        $events->assertDispatchedOnce(Attempting::class);
        $events->assertDispatchedOnce(Validated::class);
    }

    public function testAttemptDoesntRehashPasswordWhenDisabled()
    {
        $provider = Double::for(UserProvider::class);
        $guard = new SessionGuard('default', $provider, new Store('test', new ArraySessionHandler(10)), rehashOnLogin: false, timeboxDuration: 0);
        $events = new EventFake(new Dispatcher);
        $guard->setDispatcher($events);
        $user = new GenericUser(['id' => 10]);
        $provider->expects('retrieveByCredentials')->returns($user);
        $provider->expects('validateCredentials')->with($user, ['foo'])->returns(true);
        $provider->expects('rehashPasswordIfRequired')->never();
        $this->assertTrue($guard->attempt(['foo']));
        $this->assertSame($user, $guard->getUser());

        $events->assertDispatchedOnce(Attempting::class);
        $events->assertDispatchedOnce(Validated::class);
    }

    public function testLoginStoresIdentifierInSession()
    {
        [$guard, , $session] = $this->getRealGuard();
        $sessionId = $session->getId();
        $guard->login(new GenericUser(['id' => 'bar']));
        $this->assertSame('bar', $session->get($guard->getName()));
        $this->assertNotSame($sessionId, $session->getId());
    }

    public function testLoginStoresPasswordHashInSession()
    {
        [$guard, , $session] = $this->getRealGuard();
        $sessionId = $session->getId();
        $user = new GenericUser(['id' => 'foo', 'password' => 'bar']);

        $guard->login($user);

        $this->assertSame('foo', $session->get($guard->getName()));
        $this->assertSame(
            hash_hmac('sha256', 'bar', 'base-key-for-password-hash-mac'),
            $session->get('password_hash_default')
        );
        $this->assertNotSame($sessionId, $session->getId());
    }

    public function testLoginWithGenericUserWithoutPasswordDoesNotStorePasswordHash()
    {
        [$guard, , $session] = $this->getRealGuard();
        $user = new GenericUser(['id' => 'foo']);
        $warnings = [];

        set_error_handler(function ($level, $message) use (&$warnings) {
            $warnings[] = $message;

            return true;
        }, E_WARNING);

        try {
            $guard->login($user);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
        $this->assertSame('foo', $session->get($guard->getName()));
        $this->assertFalse($session->has('password_hash_default'));
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
        [$guard] = $this->getRealGuard();
        $events = new EventFake(new Dispatcher);
        $guard->setDispatcher($events);
        $guard->login(new GenericUser(['id' => 'bar']));

        $events->assertDispatchedOnce(Login::class);
        $events->assertDispatchedOnce(Authenticated::class);
    }

    public function testFailedAttemptFiresFailedEvent()
    {
        $guard = $this->getGuard();
        $events = new EventFake(new Dispatcher);
        $guard->setDispatcher($events);
        $timebox = $guard->getTimebox();
        $timebox->expects('call')->resolves(function ($callback, $microseconds) use ($timebox) {
            return $callback($timebox);
        });
        $guard->getProvider()->expects('retrieveByCredentials')->with(['foo'])->returns(null);
        $guard->getProvider()->expects('rehashPasswordIfRequired')->never();
        $guard->attempt(['foo']);

        $events->assertDispatchedOnce(Attempting::class);
        $events->assertDispatchedOnce(Failed::class);
        $events->assertNotDispatched(Validated::class);
    }

    public function testAuthenticateReturnsUserWhenUserIsNotNull()
    {
        $user = new GenericUser([]);
        $guard = $this->getGuard();
        $guard->setUser($user);

        $this->assertEquals($user, $guard->authenticate());
    }

    public function testSetUserFiresAuthenticatedEvent()
    {
        $user = new GenericUser([]);
        $guard = $this->getGuard();
        $events = new EventFake(new Dispatcher);
        $guard->setDispatcher($events);
        $guard->setUser($user);

        $events->assertDispatchedOnce(Authenticated::class);
    }

    public function testAuthenticateThrowsWhenUserIsNull()
    {
        $this->expectExceptionObject(new AuthenticationException('Unauthenticated.'));

        [$guard] = $this->getRealGuard();

        $guard->authenticate();
    }

    public function testHasUserReturnsTrueWhenUserIsNotNull()
    {
        $user = new GenericUser([]);
        $guard = $this->getGuard();
        $guard->setUser($user);

        $this->assertTrue($guard->hasUser());
    }

    public function testHasUserReturnsFalseWhenUserIsNull()
    {
        $guard = $this->getGuard();
        $guard->getSession()->expects('get')->never();

        $this->assertFalse($guard->hasUser());
    }

    public function testIsAuthedReturnsTrueWhenUserIsNotNull()
    {
        $user = new GenericUser([]);
        $mock = $this->getGuard();
        $mock->setUser($user);
        $this->assertTrue($mock->check());
        $this->assertFalse($mock->guest());
    }

    public function testIsAuthedReturnsFalseWhenUserIsNull()
    {
        [$guard] = $this->getRealGuard();

        $this->assertFalse($guard->check());
        $this->assertTrue($guard->guest());
    }

    public function testUserMethodReturnsCachedUser()
    {
        $user = new GenericUser([]);
        $mock = $this->getGuard();
        $mock->setUser($user);
        $this->assertSame($user, $mock->user());
    }

    public function testNullIsReturnedForUserIfNoUserFound()
    {
        [$guard] = $this->getRealGuard();

        $this->assertNull($guard->user());
    }

    public function testUserIsSetToRetrievedUser()
    {
        [$guard, $provider, $session] = $this->getRealGuard();
        $session->put($guard->getName(), 1);
        $user = new GenericUser([]);
        $provider->expects('retrieveById')->with(1)->returns($user);

        $this->assertSame($user, $guard->user());
        $this->assertSame($user, $guard->getUser());
    }

    public function testLogoutRemovesSessionTokenAndRememberMeCookie()
    {
        [$guard, $provider, $session] = $this->getRealGuard();
        $guard->setCookieJar($cookies = $this->getCookieJar());
        $guard->setRequest(Request::create('/', 'GET', [], [$guard->getRecallerName() => '10|a|hash']));
        $user = new GenericUser(['id' => 10, 'remember_token' => 'a']);
        $provider->expects('updateRememberToken');
        $session->put($guard->getName(), 10);
        // A cookie queued earlier in the request is replaced by the one that forgets it...
        $cookies->queue($cookies->make($guard->getRecallerName(), 'stale'));
        $guard->setUser($user);
        $guard->logout();
        $this->assertNull($guard->getUser());
        $this->assertNull($session->get($guard->getName()));
        $this->assertNotSame('a', $user->getRememberToken());
        $this->assertLessThan(time(), $cookies->queued($guard->getRecallerName())->getExpiresTime());
        $this->assertSame('', (string) $cookies->queued($guard->getRecallerName())->getValue());
    }

    public function testLogoutDoesNotEnqueueRememberMeCookieForDeletionIfCookieDoesntExist()
    {
        [$guard, , $session] = $this->getRealGuard();
        $guard->setCookieJar($cookies = $this->getCookieJar());
        $guard->setRequest(Request::create('/'));
        $user = new GenericUser(['id' => 10, 'remember_token' => null]);
        $session->put($guard->getName(), 10);
        $cookies->queue($cookies->make($guard->getRecallerName(), 'stale'));
        $guard->setUser($user);
        $guard->logout();
        $this->assertNull($guard->getUser());
        $this->assertNull($session->get($guard->getName()));
        $this->assertFalse($cookies->hasQueued($guard->getRecallerName()));
    }

    public function testLogoutFiresLogoutEvent()
    {
        [$guard, , $session] = $this->getRealGuard();
        $guard->setCookieJar($this->getCookieJar());
        $events = new EventFake(new Dispatcher);
        $guard->setDispatcher($events);
        $user = new GenericUser(['id' => 10, 'remember_token' => null]);
        $guard->setUser($user);
        $session->put($guard->getName(), $user->getAuthIdentifier());
        $guard->logout();

        $this->assertNull($session->get($guard->getName()));
        $events->assertDispatchedOnce(Authenticated::class);
        $events->assertDispatchedOnce(Logout::class);
    }

    public function testLogoutDoesNotSetRememberTokenIfNotPreviouslySet()
    {
        [$guard, $provider] = $this->getRealGuard();
        $guard->setCookieJar($this->getCookieJar());
        $guard->setRequest(Request::create('/'));
        $user = new GenericUser(['id' => 10, 'remember_token' => null]);

        $provider->expects('updateRememberToken')->never();

        $guard->setUser($user);
        $guard->logout();

        $this->assertNull($user->getRememberToken());
    }

    public function testLogoutCurrentDeviceRemovesRememberMeCookie()
    {
        [$guard, , $session] = $this->getRealGuard();
        $guard->setCookieJar($cookies = $this->getCookieJar());
        $guard->setRequest(Request::create('/', 'GET', [], [$guard->getRecallerName() => '10|a|hash']));
        $user = new GenericUser(['id' => 10, 'remember_token' => 'a']);
        $session->put($guard->getName(), 10);
        $cookies->queue($cookies->make($guard->getRecallerName(), 'stale'));
        $guard->setUser($user);
        $guard->logoutCurrentDevice();
        $this->assertNull($guard->getUser());
        $this->assertNull($session->get($guard->getName()));
        $this->assertSame('a', $user->getRememberToken());
        $this->assertLessThan(time(), $cookies->queued($guard->getRecallerName())->getExpiresTime());
        $this->assertSame('', (string) $cookies->queued($guard->getRecallerName())->getValue());
    }

    public function testLogoutCurrentDeviceDoesNotEnqueueRememberMeCookieForDeletionIfCookieDoesntExist()
    {
        [$guard, , $session] = $this->getRealGuard();
        $guard->setCookieJar($cookies = $this->getCookieJar());
        $guard->setRequest(Request::create('/'));
        $user = new GenericUser(['id' => 10]);
        $session->put($guard->getName(), 10);
        $cookies->queue($cookies->make($guard->getRecallerName(), 'stale'));
        $guard->setUser($user);
        $guard->logoutCurrentDevice();
        $this->assertNull($guard->getUser());
        $this->assertNull($session->get($guard->getName()));
        $this->assertFalse($cookies->hasQueued($guard->getRecallerName()));
    }

    public function testLogoutCurrentDeviceFiresLogoutEvent()
    {
        [$guard, , $session] = $this->getRealGuard();
        $guard->setCookieJar($this->getCookieJar());
        $events = new EventFake(new Dispatcher);
        $guard->setDispatcher($events);
        $user = new GenericUser(['id' => 10]);
        $guard->setUser($user);
        $session->put($guard->getName(), $user->getAuthIdentifier());
        $guard->logoutCurrentDevice();

        $this->assertNull($session->get($guard->getName()));
        $events->assertDispatchedOnce(Authenticated::class);
        $events->assertDispatchedOnce(CurrentDeviceLogout::class);
    }

    public function testLoginMethodQueuesCookieWhenRemembering()
    {
        [$guard, $provider, $session] = $this->getRealGuard();
        $guard->setCookieJar($cookies = $this->getCookieJar());
        $expectedHash = hash_hmac('sha256', 'bar', 'base-key-for-password-hash-mac');
        $user = new GenericUser(['id' => 'foo', 'password' => 'bar', 'remember_token' => 'recaller']);
        $provider->expects('updateRememberToken')->never();
        $guard->login($user, true);

        $cookie = $cookies->queued($guard->getRecallerName());
        $this->assertSame('foo|recaller|'.$expectedHash, $cookie->getValue());
        $this->assertEqualsWithDelta(time() + 576000 * 60, $cookie->getExpiresTime(), 5);
        $this->assertSame('foo', $session->get($guard->getName()));
        $this->assertSame($expectedHash, $session->get('password_hash_default'));
        $this->assertSame('recaller', $user->getRememberToken());
    }

    public function testLoginMethodQueuesCookieWhenRememberingAndAllowsOverride()
    {
        [$guard, $provider] = $this->getRealGuard();
        $guard->setRememberDuration(5000);
        $guard->setCookieJar($cookies = $this->getCookieJar());
        $expectedHash = hash_hmac('sha256', 'bar', 'base-key-for-password-hash-mac');
        $user = new GenericUser(['id' => 'foo', 'password' => 'bar', 'remember_token' => 'recaller']);
        $provider->expects('updateRememberToken')->never();
        $guard->login($user, true);

        $cookie = $cookies->queued($guard->getRecallerName());
        $this->assertSame('foo|recaller|'.$expectedHash, $cookie->getValue());
        $this->assertEqualsWithDelta(time() + 5000 * 60, $cookie->getExpiresTime(), 5);
        $this->assertSame('recaller', $user->getRememberToken());
    }

    public function testLoginMethodCreatesRememberTokenIfOneDoesntExist()
    {
        [$guard, $provider, $session] = $this->getRealGuard();
        $guard->setCookieJar($cookies = $this->getCookieJar());
        $expectedHash = hash_hmac('sha256', 'foo', 'base-key-for-password-hash-mac');
        $user = new GenericUser(['id' => 'foo', 'password' => 'foo', 'remember_token' => null]);
        $provider->expects('updateRememberToken');
        $guard->login($user, true);

        $this->assertNotEmpty($user->getRememberToken());
        $this->assertSame(
            'foo|'.$user->getRememberToken().'|'.$expectedHash,
            $cookies->queued($guard->getRecallerName())->getValue()
        );
        $this->assertSame($expectedHash, $session->get('password_hash_default'));
    }

    public function testLoginUsingIdLogsInWithUser()
    {
        [$guard, $provider, $session] = $this->getRealGuard();

        $user = new GenericUser(['id' => 10, 'password' => 'secret']);
        $provider->expects('retrieveById')->with(10)->returns($user);

        $this->assertSame($user, $guard->loginUsingId(10));
        $this->assertSame($user, $guard->user());
        $this->assertSame(10, $session->get($guard->getName()));
    }

    public function testLoginUsingIdFailure()
    {
        [$guard, $provider, $session] = $this->getRealGuard();

        $provider->expects('retrieveById')->with(11)->returns(null);

        $this->assertFalse($guard->loginUsingId(11));
        $this->assertNull($guard->user());
        $this->assertNull($session->get($guard->getName()));
    }

    public function testOnceUsingIdSetsUser()
    {
        [$guard, $provider, $session] = $this->getRealGuard();

        $user = new GenericUser(['id' => 10, 'password' => 'secret']);
        $provider->expects('retrieveById')->with(10)->returns($user);

        $this->assertSame($user, $guard->onceUsingId(10));
        $this->assertSame($user, $guard->user());
        $this->assertNull($session->get($guard->getName()));
    }

    public function testOnceUsingIdFailure()
    {
        [$guard, $provider] = $this->getRealGuard();

        $provider->expects('retrieveById')->with(11)->returns(null);

        $this->assertFalse($guard->onceUsingId(11));
        $this->assertNull($guard->user());
    }

    public function testUserUsesRememberCookieIfItExists()
    {
        [$guard, $provider, $session] = $this->getRealGuard();
        $sessionId = $session->getId();
        $guard->setRequest(Request::create('/', 'GET', [], [$guard->getRecallerName() => 'id|recaller|baz']));
        $user = new GenericUser(['id' => 'bar', 'password' => 'baz']);
        $provider->expects('retrieveByToken')->with('id', 'recaller')->returns($user);

        $this->assertSame($user, $guard->user());
        $this->assertTrue($guard->viaRemember());
        $this->assertSame('bar', $session->get($guard->getName()));
        $this->assertNotSame($sessionId, $session->getId());
    }

    public function testUserReturnsNullWhenRememberCookieTokenDoesNotMatchAnyUser()
    {
        $guard = $this->getGuard();
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $request = Request::create('/', 'GET', [], [$guard->getRecallerName() => 'id|recaller|baz']);
        $guard = new SessionGuard('default', $provider, $session, $request);
        $guard->getSession()->expects('get')->with($guard->getName())->returns(null);
        $guard->getProvider()->expects('retrieveByToken')->with('id', 'recaller')->returns(null);
        $this->assertNull($guard->user());
        $this->assertFalse($guard->viaRemember());
    }

    public function testUserReturnsNullWhenRecallerUserHasNullPassword()
    {
        $guard = $this->getGuard();
        [$session, $provider, $request, $cookie] = $this->getMocks();
        $request = Request::create('/', 'GET', [], [$guard->getRecallerName() => 'id|recaller|baz']);
        $guard = new SessionGuard('default', $provider, $session, $request);
        $guard->getSession()->expects('get')->with($guard->getName())->returns(null);
        $user = new GenericUser(['id' => 'bar']);
        $guard->getProvider()->expects('retrieveByToken')->with('id', 'recaller')->returns($user);
        $this->assertNull($guard->user());
    }

    public function testHashPasswordForCookieAcceptsNullPassword()
    {
        $guard = $this->getGuard();
        $deprecations = [];

        set_error_handler(function ($level, $message) use (&$deprecations) {
            $deprecations[] = $message;

            return true;
        }, E_DEPRECATED);

        try {
            $hash = $guard->hashPasswordForCookie(null);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
        $this->assertSame($guard->hashPasswordForCookie(''), $hash);
    }

    public function testLoginOnceSetsUser()
    {
        [$guard, $provider, $session] = $this->getRealGuard();

        $user = new GenericUser(['id' => 10, 'password' => 'secret']);
        $provider->expects('retrieveByCredentials')->with(['foo'])->returns($user);
        $provider->expects('validateCredentials')->with($user, ['foo'])->returns(true);
        $provider->expects('rehashPasswordIfRequired')->with($user, ['foo']);

        $this->assertTrue($guard->once(['foo']));
        $this->assertSame($user, $guard->user());
        $this->assertNull($session->get($guard->getName()));
    }

    public function testLoginOnceFailure()
    {
        [$guard, $provider] = $this->getRealGuard();

        $user = new GenericUser(['id' => 10, 'password' => 'secret']);
        $provider->expects('retrieveByCredentials')->with(['foo'])->returns($user);
        $provider->expects('validateCredentials')->with($user, ['foo'])->returns(false);
        $provider->expects('rehashPasswordIfRequired')->never();

        $this->assertFalse($guard->once(['foo']));
        $this->assertNull($guard->user());
    }

    public function testForgetUserSetsUserToNull()
    {
        $user = new GenericUser([]);
        $guard = $this->getGuard();
        $guard->setUser($user);
        $guard->forgetUser();
        $this->assertNull($guard->getUser());
    }

    protected function getRealGuard()
    {
        $session = new Store('test', new ArraySessionHandler(10));
        $provider = Double::for(UserProvider::class);

        return [new SessionGuard('default', $provider, $session, timeboxDuration: 0), $provider, $session];
    }

    protected function getGuard()
    {
        [$session, $provider, $request, $cookie, $timebox] = $this->getMocks();

        return new SessionGuard('default', $provider, $session, $request, $timebox);
    }

    protected function getMocks()
    {
        return [
            Double::for(Session::class),
            Double::for(UserProvider::class),
            Request::create('/', 'GET'),
            Double::for(CookieJar::class),
            Double::for(Timebox::class),
        ];
    }

    protected function getCookieJar()
    {
        return new CookieJar(Request::create('/foo', 'GET'), Double::for(Encrypter::class), ['domain' => 'foo.com', 'path' => '/', 'secure' => false, 'httpOnly' => false]);
    }
}
