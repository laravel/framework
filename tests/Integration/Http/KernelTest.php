<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Orchestra\Testbench\Foundation\Http\Kernel as HttpKernel;
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

        $this->assertEquals(1, IntegrationTerminatingMiddleware::$terminateCalled);
        $this->assertEquals(1, IntegrationTerminatingMiddleware::$timesInstantiated);
    }
}

class IntegrationKernelTest extends HttpKernel
{
    public function __construct(Application $app, Router $router)
    {
        parent::__construct($app, $router);
        $this->pushMiddleware(IntegrationTerminatingMiddleware::class);
    }
}

class IntegrationTerminatingMiddleware {
    public static $timesInstantiated = 0;

    public static $terminateCalled = 0;

    public function __construct()
    {
        self::$timesInstantiated++;
    }

    public function handle($request, $next)
    {
        return $next($request);
    }

    public function terminate()
    {
        self::$terminateCalled++;
    }
}
