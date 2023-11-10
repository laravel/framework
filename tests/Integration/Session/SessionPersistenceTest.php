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

class SessionPersistenceTest extends TestCase
{
    public function testSessionIsPersistedEvenIfExceptionIsThrownFromRoute()
    {
        $handler = new FakeNullSessionHandler;
        $this->assertFalse($handler->written);

        Session::extend('fake-null', function () use ($handler) {
            return $handler;
        });

        Route::get('/', function () {
            throw new TokenMismatchException;
        })->middleware('web');

        $this->get('/');
        $this->assertTrue($handler->written);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app->instance(
            ExceptionHandler::class,
            $handler = m::mock(ExceptionHandler::class)->shouldIgnoreMissing()
        );

        $handler->shouldReceive('render')->andReturn(new Response);

        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('session.driver', 'fake-null');
        $app['config']->set('session.expire_on_close', true);
    }
}

class FakeNullSessionHandler extends NullSessionHandler
{
    public $written = false;

    public function write($sessionId, $data): bool
    {
        $this->written = true;

        return true;
    }
}
