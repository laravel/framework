<?php

namespace Illuminate\Tests\Integration\Session;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

class CookieSessionHandlerTest extends TestCase
{
    public function testCookieSessionDriverCookiesCanExpireOnClose()
    {
        Route::get('/', fn () => '')->middleware('web');

        $response = $this->get('/');
        $sessionIdCookie = $response->getCookie('laravel_session');
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Cookie::class, $sessionIdCookie);
        $sessionValueCookie = $response->getCookie($sessionIdCookie->getValue());

        $this->assertSame(0, $sessionIdCookie->getExpiresTime());
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Cookie::class, $sessionValueCookie);
        $this->assertSame(0, $sessionValueCookie->getExpiresTime());
    }

    public function testCookieSessionInheritsRequestSecureState()
    {
        Route::get('/', fn () => '')->middleware('web');

        $unsecureResponse = $this->get('/');
        $unsecureSessionIdCookie = $unsecureResponse->getCookie('laravel_session');
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Cookie::class, $unsecureSessionIdCookie);
        $unsecureSessionValueCookie = $unsecureResponse->getCookie($unsecureSessionIdCookie->getValue());

        $this->assertFalse($unsecureSessionIdCookie->isSecure());
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Cookie::class, $unsecureSessionValueCookie);
        $this->assertFalse($unsecureSessionValueCookie->isSecure());

        $secureResponse = $this->get('https://localhost/');
        $secureSessionIdCookie = $secureResponse->getCookie('laravel_session');
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Cookie::class, $secureSessionIdCookie);
        $secureSessionValueCookie = $secureResponse->getCookie($secureSessionIdCookie->getValue());

        $this->assertTrue($secureSessionIdCookie->isSecure());
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Cookie::class, $secureSessionValueCookie);
        $this->assertTrue($secureSessionValueCookie->isSecure());
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('session.driver', 'cookie');
        $app['config']->set('session.expire_on_close', true);
    }
}
