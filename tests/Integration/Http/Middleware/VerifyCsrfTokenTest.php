<?php

namespace Illuminate\Tests\Integration\Http\Middleware;

use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\Store;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class VerifyCsrfTokenTest extends TestCase
{
    public function testHasDefaultCookieName()
    {
        $encrypter = new Encrypter(str_repeat('a', 16));

        $request = new Request;
        $request->setLaravelSession($session = m::mock(Store::class));
        $session->shouldReceive('token')->andReturn('token');

        $middleware = new VerifyCsrfToken(app(), $encrypter);

        $response = $middleware->handle($request, function () {
            return new Response();
        });

        $cookies = $response->headers->getCookies();
        $this->assertSame('XSRF-TOKEN', $cookies[0]->getName());
    }

    public function testCanSetCookieName()
    {
        $this->app['config']->set('session.csrf_cookie', 'MY-XSRF-TOKEN');

        $encrypter = new Encrypter(str_repeat('a', 16));

        $request = new Request;
        $request->setLaravelSession($session = m::mock(Store::class));
        $session->shouldReceive('token')->andReturn('token');

        $middleware = new VerifyCsrfToken(app(), $encrypter);

        $response = $middleware->handle($request, function () {
            return new Response();
        });

        $cookies = $response->headers->getCookies();
        $this->assertSame('MY-XSRF-TOKEN', $cookies[0]->getName());
    }
}
