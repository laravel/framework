<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Foundation\Http\Kernel as BaseKernel;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Closure;

/**
 * @group integration
 */
class DisablesGlobalMiddlewareTest extends TestCase
{
    /**
     * Resolve application HTTP Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton(Kernel::class, DummyKernel::class);
    }

    public function testItCanDisableGlobalMiddleware()
    {
        $action = function () {return response('route-response');};
        
        $router = $this->app->make(Registrar::class);
        
        $router->get('foo-route', $action); 
        $router->get('bar-route', $action)->withoutMiddleware(DummyMiddleware::class);
        
        $this->get('foo-route')->assertSee('middleware-response');
        $this->get('bar-route')->assertSee('route-response');
    }
}

class DummyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        return response('middleware-response');
    }
}

class DummyKernel extends BaseKernel
{
    /**
     * The application's middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        DummyMiddleware::class
    ];
}
