<?php

namespace Illuminate\Tests\Integration\Routing\FluentRoutingTest;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * @group integration
 */
class FluentRoutingTest extends TestCase
{
    public function test_middleware_run_when_registered_as_array_or_params()
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

        $this->assertEquals('middleware output', $this->get('one')->content());
        $this->assertEquals('middleware output', $this->get('two')->content());
        $this->assertEquals('middleware output', $this->get('three')->content());
        $this->assertEquals('middleware output', $this->get('four')->content());
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
