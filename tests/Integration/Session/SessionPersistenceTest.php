<?php

namespace Illuminate\Tests\Integration\Session;

use Mockery;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Session\NullSessionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Contracts\Debug\ExceptionHandler;

/**
 * @group integration
 */
class SessionPersistenceTest extends TestCase
{
    public function test_session_is_persisted_even_if_exception_is_thrown_from_route()
    {
        $handler = new FakeNullSessionHandler;
        $this->assertFalse($handler->written);

        Session::extend('fake-null', function () use ($handler) {
            return $handler;
        });

        Route::get('/', function () {
            throw new TokenMismatchException;
        })->middleware('web');

        $response = $this->get('/');
        $this->assertTrue($handler->written);
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
        $app['config']->set('session.expire_on_close', true);
    }
}

class FakeNullSessionHandler extends NullSessionHandler
{
    public $written = false;

    public function write($sessionId, $data)
    {
        $this->written = true;

        return true;
    }
}
