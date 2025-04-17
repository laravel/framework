<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class FluentRoutingTest extends TestCase
{
    public static $value = '';

    public function testMiddlewareRunWhenRegisteredAsArrayOrParams()
    {
        $controller = function () {
            return 'Hello World';
        };

        Route::middleware(Middleware::class, Middleware2::class)
            ->get('before', $controller);

        Route::get('after', $controller)
            ->middleware(Middleware::class, Middleware2::class);

        Route::middleware([Middleware::class, Middleware2::class])
            ->get('before_array', $controller);

        Route::get('after_array', $controller)
            ->middleware([Middleware::class, Middleware2::class]);

        Route::middleware(Middleware::class)
            ->get('before_after', $controller)
            ->middleware([Middleware2::class]);

        Route::middleware(Middleware::class)
            ->middleware(Middleware2::class)
            ->get('both_before', $controller);

        Route::get('both_after', $controller)
            ->middleware(Middleware::class)
            ->middleware(Middleware2::class);

        $this->assertSame('1_2', $this->get('before')->content());
        $this->assertSame('1_2', $this->get('after')->content());
        $this->assertSame('1_2', $this->get('before_array')->content());
        $this->assertSame('1_2', $this->get('after_array')->content());
        $this->assertSame('1_2', $this->get('before_after')->content());
        $this->assertSame('1_2', $this->get('both_before')->content());
        $this->assertSame('1_2', $this->get('both_after')->content());
        $this->assertSame('1_2', $this->get('both_after')->content());
    }

    public function testEmptyMiddlewareGroupAreHandledGracefully()
    {
        $controller = function () {
            return 'Hello World';
        };

        Route::middlewareGroup('public', []);

        Route::middleware('public')
            ->get('public', $controller);

        $this->assertSame('Hello World', $this->get('public')->content());
    }

    public function test_route_when_executes_callback_when_condition_is_true()
    {
        Route::when(true, function () {
            Route::get('test-when-true', function () {
                return 'success';
            });
        });

        $this->assertSame('success', $this->get('test-when-true')->content());
        $this->get('test-when-true')->assertStatus(200)->isOk();

    }

    public function test_route_when_does_not_execute_callback_when_condition_is_false()
    {
        Route::when(false, function () {
            Route::get('test-when-false', function () {
                return 'success';
            });
        });

        $this->assertNotSame('success', $this->get('test-when-false')->content());
        $this->get('test-when-false')->assertNotFound();

    }
}

class Middleware
{
    public function handle($request, $next)
    {
        FluentRoutingTest::$value = '1';

        return $next($request);
    }
}

class Middleware2
{
    public function handle()
    {
        return FluentRoutingTest::$value.'_2';
    }
}
