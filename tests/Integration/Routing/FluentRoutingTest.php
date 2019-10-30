<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class FluentRoutingTest extends TestCase
{
    public function testMiddlewareRunWhenRegisteredAsArrayOrParams()
    {
        Route::middleware(Middleware::class, Middleware2::class)
            ->get('one', function () {
                return 'Hello World';
            });

        Route::get('two', function () {
            return 'Hello World';
        })->middleware(Middleware::class, Middleware2::class);

        Route::middleware([Middleware::class, Middleware2::class])
            ->get('three', function () {
                return 'Hello World';
            });

        Route::get('four', function () {
            return 'Hello World';
        })->middleware([Middleware::class, Middleware2::class]);

        $this->assertSame('middleware output', $this->get('one')->content());
        $this->assertSame('middleware output', $this->get('two')->content());
        $this->assertSame('middleware output', $this->get('three')->content());
        $this->assertSame('middleware output', $this->get('four')->content());
    }
}

class Middleware
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}

class Middleware2
{
    public function handle()
    {
        return 'middleware output';
    }
}
