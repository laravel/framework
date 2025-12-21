<?php

namespace Illuminate\Tests\Session\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Store;
use Mockery;
use PHPUnit\Framework\TestCase;

class AuthenticateSessionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_handle_without_session()
    {
        $request = new Request;
        $next = fn () => 'next-1';

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('viaRemember')->never();

        $middleware = new AuthenticateSession($authFactory);
        $response = $middleware->handle($request, $next);
        $this->assertEquals('next-1', $response);
    }

    public function test_handle_with_session_without_request_user()
    {
        $request = new Request;

        // set session:
        $request->setLaravelSession(new Store('name', new ArraySessionHandler(1)));

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('viaRemember')->never();

        $next = fn () => 'next-2';
        $middleware = new AuthenticateSession($authFactory);
        $response = $middleware->handle($request, $next);
        $this->assertEquals('next-2', $response);
    }

    public function test_handle_with_session_without_auth_password()
    {
        $user = new class
        {
            public function getAuthPassword()
            {
                return null;
            }
        };

        $request = new Request;

        // set session:
        $request->setLaravelSession(new Store('name', new ArraySessionHandler(1)));
        // set a password-less user:
        $request->setUserResolver(fn () => $user);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('viaRemember')->never();

        $next = fn () => 'next-3';
        $middleware = new AuthenticateSession($authFactory);
        $response = $middleware->handle($request, $next);

        $this->assertEquals('next-3', $response);
    }

    public function test_handle_with_session_with_user_auth_password_on_request_via_remember_false()
    {
        $user = new class
        {
            public function getAuthPassword()
            {
                return 'my-pass-(*&^%$#!@';
            }
        };

        $request = new Request;
        $request->setUserResolver(fn () => $user);

        $session = new Store('name', new ArraySessionHandler(1));
        $request->setLaravelSession($session);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('viaRemember')->andReturn(false);
        $authFactory->shouldReceive('getDefaultDriver')->andReturn('web');
        $authFactory->shouldReceive('user')->andReturn(null);

        $middleware = new AuthenticateSession($authFactory);
        $response = $middleware->handle($request, fn () => 'next-4');

        $this->assertEquals('my-pass-(*&^%$#!@', $session->get('password_hash_web'));
        $this->assertEquals('next-4', $response);
    }

    public function test_handle_with_invalid_password_hash()
    {
        $user = new class
        {
            public function getAuthPassword()
            {
                return 'my-pass-(*&^%$#!@';
            }
        };

        $request = new Request(cookies: ['recaller-name' => 'a|b|my-pass-dont-match']);
        $request->setUserResolver(fn () => $user);

        $session = new Store('name', new ArraySessionHandler(1));
        $session->put('a', '1');
        $session->put('b', '2');
        // set session:
        $request->setLaravelSession($session);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('viaRemember')->andReturn(true);
        $authFactory->shouldReceive('getRecallerName')->once()->andReturn('recaller-name');
        $authFactory->shouldReceive('logoutCurrentDevice')->once()->andReturn(null);
        $authFactory->shouldReceive('getDefaultDriver')->andReturn('web');
        $authFactory->shouldReceive('user')->andReturn(null);

        $this->assertNotNull($session->get('a'));
        $this->assertNotNull($session->get('b'));
        AuthenticateSession::redirectUsing(fn ($request) => 'i-wanna-go-home');

        // act:
        $middleware = new AuthenticateSession($authFactory);

        $message = '';
        try {
            $middleware->handle($request, fn () => 'next-7');
        } catch (AuthenticationException $e) {
            $message = $e->getMessage();
            $this->assertEquals('i-wanna-go-home', $e->redirectTo($request));
        }
        $this->assertEquals('Unauthenticated.', $message);

        // ensure session is flushed:
        $this->assertNull($session->get('a'));
        $this->assertNull($session->get('b'));
    }

    public function test_handle_with_invalid_incookie_password_hash_via_remember_true()
    {
        $user = new class
        {
            public function getAuthPassword()
            {
                return 'my-pass-(*&^%$#!@';
            }
        };

        $request = new Request(cookies: ['recaller-name' => 'a|b|my-pass-dont-match']);
        $request->setUserResolver(fn () => $user);

        $session = new Store('name', new ArraySessionHandler(1));
        $session->put('a', '1');
        $session->put('b', '2');
        // set session:
        $request->setLaravelSession($session);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('viaRemember')->andReturn(true);
        $authFactory->shouldReceive('getRecallerName')->once()->andReturn('recaller-name');
        $authFactory->shouldReceive('logoutCurrentDevice')->once();
        $authFactory->shouldReceive('getDefaultDriver')->andReturn('web');
        $authFactory->shouldReceive('user')->andReturn(null);

        $middleware = new AuthenticateSession($authFactory);
        // act:
        try {
            $message = '';
            $middleware->handle($request, fn () => 'next-6');
        } catch (AuthenticationException $e) {
            $message = $e->getMessage();
        }
        $this->assertEquals('Unauthenticated.', $message);

        // ensure session is flushed
        $this->assertNull($session->get('password_hash_web'));
        $this->assertNull($session->get('a'));
        $this->assertNull($session->get('b'));
    }

    public function test_handle_with_valid_incookie_invalid_insession_hash_via_remember_true()
    {
        $user = new class
        {
            public function getAuthPassword()
            {
                return 'my-pass-(*&^%$#!@';
            }
        };

        $request = new Request(cookies: ['recaller-name' => 'a|b|my-pass-(*&^%$#!@']);
        $request->setUserResolver(fn () => $user);

        $session = new Store('name', new ArraySessionHandler(1));
        $session->put('a', '1');
        $session->put('b', '2');
        $session->put('password_hash_web', 'invalid-password');
        // set session on the request:
        $request->setLaravelSession($session);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('viaRemember')->andReturn(true);
        $authFactory->shouldReceive('getRecallerName')->once()->andReturn('recaller-name');
        $authFactory->shouldReceive('logoutCurrentDevice')->once()->andReturn(null);
        $authFactory->shouldReceive('getDefaultDriver')->andReturn('web');
        $authFactory->shouldReceive('user')->andReturn(null);

        // act:
        $middleware = new AuthenticateSession($authFactory);
        try {
            $message = '';
            $middleware->handle($request, fn () => 'next-7');
        } catch (AuthenticationException $e) {
            $message = $e->getMessage();
        }
        $this->assertEquals('Unauthenticated.', $message);

        // ensure session is flushed:
        $this->assertNull($session->get('password_hash_web'));
        $this->assertNull($session->get('a'));
        $this->assertNull($session->get('b'));
    }

    public function test_handle_with_valid_password_in_session_cookie_is_empty_guard_has_user()
    {
        $user = new class
        {
            public function getAuthPassword()
            {
                return 'my-pass-(*&^%$#!@';
            }
        };

        $request = new Request(cookies: ['recaller-name' => 'a|b']);
        $request->setUserResolver(fn () => $user);

        $session = new Store('name', new ArraySessionHandler(1));
        $session->put('a', '1');
        $session->put('b', '2');
        $session->put('password_hash_web', 'my-pass-(*&^%$#!@');
        // set session on the request:
        $request->setLaravelSession($session);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('viaRemember')->andReturn(false);
        $authFactory->shouldReceive('getRecallerName')->never();
        $authFactory->shouldReceive('logoutCurrentDevice')->never();
        $authFactory->shouldReceive('getDefaultDriver')->andReturn('web');
        $authFactory->shouldReceive('user')->andReturn($user);

        // act:
        $middleware = new AuthenticateSession($authFactory);
        $response = $middleware->handle($request, fn () => 'next-8');

        $this->assertEquals('next-8', $response);
        // ensure session is flushed:
        $this->assertEquals('my-pass-(*&^%$#!@', $session->get('password_hash_web'));
        $this->assertEquals('1', $session->get('a'));
        $this->assertEquals('2', $session->get('b'));
    }
}
