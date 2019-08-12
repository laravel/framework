<?php

namespace Illuminate\Tests\Integration\Cookie;

use Mockery;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Session\NullSessionHandler;
use Illuminate\Contracts\Debug\ExceptionHandler;

/**
 * @group integration
 */
class CookieTest extends TestCase
{
    public function test_cookie_is_sent_back_with_proper_expire_time_when_should_expire_on_close()
    {
        $this->app['config']->set('session.expire_on_close', true);

        Route::get('/', function () {
            return 'hello world';
        })->middleware('web');

        $response = $this->get('/');
        $this->assertCount(2, $response->headers->getCookies());
        $this->assertEquals(0, ($response->headers->getCookies()[1])->getExpiresTime());
    }

    public function test_cookie_is_sent_back_with_proper_expire_time_with_respect_to_lifetime()
    {
        $this->app['config']->set('session.expire_on_close', false);
        $this->app['config']->set('session.lifetime', 1);

        Route::get('/', function () {
            return 'hello world';
        })->middleware('web');

        Carbon::setTestNow(Carbon::now());
        $response = $this->get('/');
        $this->assertCount(2, $response->headers->getCookies());
        $this->assertEquals(Carbon::now()->getTimestamp() + 60, ($response->headers->getCookies()[1])->getExpiresTime());
    }

    protected function getEnvironmentSetUp($app)
    {
        $app->instance(
            ExceptionHandler::class,
            $handler = Mockery::mock(ExceptionHandler::class)->shouldIgnoreMissing()
        );

        $handler->shouldReceive('render')->andReturn(new Response);

        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('session.driver', 'fake-null');

        Session::extend('fake-null', function () {
            return new NullSessionHandler;
        });
    }
}
