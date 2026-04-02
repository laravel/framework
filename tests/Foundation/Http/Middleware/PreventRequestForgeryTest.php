<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Session\TokenMismatchException;
use PHPUnit\Framework\TestCase;

class PreventRequestForgeryTest extends TestCase
{
    public function test_throws_a_token_mismatch_exception_when_origin_is_valid_but_token_is_invalid(): void
    {
        // We need to fake that we are not running in console or unit tests
        $app = $this->createMock(Application::class);
        $app->method('runningInConsole')->willReturn(false);
        $app->method('runningUnitTests')->willReturn(false);

        $session = new Store('test', new ArraySessionHandler(2));
        $session->put('_token', 'valid-token');

        // Request with 'same-origin' header but WRONG token
        $request = Request::create('/jobs', 'POST', ['_token' => 'wrong-token']);
        $request->headers->set('Sec-Fetch-Site', 'same-origin');
        $request->setLaravelSession($session);

        $middleware = new PreventRequestForgery(
            $app,
            $this->createMock(Encrypter::class)
        );

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, function () {
        });
    }
}
