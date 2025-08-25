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
        $sessionValueCookie = $response->getCookie($sessionIdCookie->getValue());

        $this->assertEquals(0, $sessionIdCookie->getExpiresTime());
        $this->assertEquals(0, $sessionValueCookie->getExpiresTime());
    }

    public function testCookieSessionInheritsRequestSecureState()
    {
        Route::get('/', fn () => '')->middleware('web');

        $unsecureResponse = $this->get('/');
        $unsecureSessionIdCookie = $unsecureResponse->getCookie('laravel_session');
        $unsecureSessionValueCookie = $unsecureResponse->getCookie($unsecureSessionIdCookie->getValue());

        $this->assertFalse($unsecureSessionIdCookie->isSecure());
        $this->assertFalse($unsecureSessionValueCookie->isSecure());

        $secureResponse = $this->get('https://localhost/');
        $secureSessionIdCookie = $secureResponse->getCookie('laravel_session');
        $secureSessionValueCookie = $secureResponse->getCookie($secureSessionIdCookie->getValue());

        $this->assertTrue($secureSessionIdCookie->isSecure());
        $this->assertTrue($secureSessionValueCookie->isSecure());
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('session.driver', 'cookie');
        $app['config']->set('session.expire_on_close', true);
    }
}
