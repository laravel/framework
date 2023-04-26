<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Foundation\Http\Kernel as HttpKernel;
use Orchestra\Testbench\TestCase;

class KernelTest extends TestCase
{
    protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton('Illuminate\Contracts\Http\Kernel', IntegrationKernelTest::class);
    }

    public function testMiddlewareIsCalledOnce()
    {
        Route::get('/');

        $this->getJson('/');

        $this->assertEquals(1, IntegrationTerminatingMiddleware::$timesTerminateCalled);
        $this->assertEquals(2, IntegrationTerminatingMiddleware::$timesInstantiated);
        $this->assertEquals(1, IntegrationMiddleware::$timesInstantiated);
    }
}

class IntegrationKernelTest extends HttpKernel
{
    public function __construct(Application $app, Router $router)
    {
        parent::__construct($app, $router);
        $this->pushMiddleware(IntegrationTerminatingMiddleware::class);
        $this->pushMiddleware(IntegrationMiddleware::class);
    }
}

class IntegrationMiddleware
{
    public static $timesInstantiated = 0;

    public function __construct()
    {
        static::$timesInstantiated++;
    }

    public function handle($request, $next)
    {
        return $next($request);
    }
}
class IntegrationTerminatingMiddleware
{
    public static $timesInstantiated = 0;

    public static $timesTerminateCalled = 0;

    public function __construct()
    {
        static::$timesInstantiated++;
    }

    public function handle($request, $next)
    {
        return $next($request);
    }
    public function terminate()
    {
        static::$timesTerminateCalled++;
    }
}
