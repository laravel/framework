<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class MiddlewareTest extends TestCase
{

    public function test_request_in_middleware_is_shared()
    {
        $this->app->singleton('service', function ($app) {
            return $app['request']->attributes->get('middleware');
        });

        Route::get('/', function () {
            return app('service');
        })->middleware(MiddlewareStub::class);

        $response = $this->get('/');

        $this->assertEquals('yes', $response->getContent());
    }
}

class MiddlewareStub
{

    public function handle($request, $next)
    {
        $request->attributes->set('middleware', 'yes');

        return $next($request);
    }
}
