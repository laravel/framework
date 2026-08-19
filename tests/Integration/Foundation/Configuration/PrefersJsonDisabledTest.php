<?php

namespace Illuminate\Tests\Integration\Foundation\Configuration;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class PrefersJsonDisabledTest extends TestCase
{
    protected function resolveApplication()
    {
        return Application::configure(static::applicationBasePath())
            ->withMiddleware()
            ->create();
    }

    public function testPlainStringRouteReturnsHtmlUnderWildcardAcceptWhenDisabled()
    {
        Route::get('plain', fn () => 'hello');

        $this->get('plain', ['Accept' => '*/*'])
            ->assertOk()
            ->assertSee('hello')
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function testUnauthenticatedWildcardStillRedirectsWhenDisabled()
    {
        Route::get('login', fn () => 'login page')->name('login');

        Route::get('protected', fn () => 'secret')->middleware(Authenticate::class);

        $this->get('protected', ['Accept' => '*/*'])
            ->assertRedirect();
    }
}
