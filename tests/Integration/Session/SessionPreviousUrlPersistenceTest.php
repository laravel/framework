<?php

namespace Illuminate\Tests\Integration\Session;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Session\NullSessionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class SessionPreviouslUrlPersistenceTest extends TestCase
{
    public function testRemembersPreviouslyVisitedUrl()
    {
        Route::middleware('web')->get('_test/session-url/home', function () {
            return url()->previous();
        });

        Route::middleware('web')->get('_test/session-url/my-page', function () {
            return 'my-page-content';
        });

        $this->get('_test/session-url/my-page')
            ->assertOk()
            ->assertSee('my-page-content');

        $this->get('_test/session-url/home')
            ->assertOk()
            ->assertSee(url('_test/session-url/my-page'));
    }

    public function testIgnoresTurboFrameVisitsFromPreviousUrl()
    {
        Route::middleware('web')->get('_test/session-url/home', function () {
            return url()->previous();
        });

        Route::middleware('web')->get('_test/session-url/my-page', function () {
            return 'my-page-content';
        });

        $this->withHeaders(['Turbo-Frame' => 'testing-frame'])
            ->get('_test/session-url/my-page')
            ->assertOk()
            ->assertSee('my-page-content');

        $this->get('_test/session-url/home')
            ->assertOk()
            ->assertSee(url(''))
            ->assertDontSee(url('_test/session-url/my-page'));
    }

    protected function getEnvironmentSetUp($app)
    {
        $app->instance(
            ExceptionHandler::class,
            $handler = m::mock(ExceptionHandler::class)->shouldIgnoreMissing()
        );

        $handler->shouldReceive('render')->andReturn(new Response);

        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('session.driver', 'array');
        $app['config']->set('session.expire_on_close', true);
    }
}
