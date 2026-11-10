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

    public function testCookieSessionSurvivesInvalidUtf8Input()
    {
        Route::post('/form', function () {
            session()->put('user_id', 1);

            return back()->withInput();
        })->middleware('web');

        Route::get('/form', fn () => [
            'user_id' => session('user_id'),
            'name' => bin2hex(old('name', '')),
        ])->middleware('web');

        $response = $this->post('/form', ['name' => "\xB1"]);

        $sessionId = $response->getCookie('laravel_session')->getValue();

        $this->app['session']->forgetDrivers();

        $this->withCookies([
            'laravel_session' => $sessionId,
            $sessionId => $response->getCookie($sessionId)->getValue(),
        ])->get('/form')->assertExactJson(['user_id' => 1, 'name' => 'b1']);
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('session.driver', 'cookie');
        $app['config']->set('session.expire_on_close', true);
    }
}
