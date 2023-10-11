<?php

namespace Illuminate\Tests\Foundation\Http;

use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;

class KernelRouteGroupTest extends TestCase
{
    public function testMiddlewareGroupsInRouterAndKernel()
    {
        $router = $this->getRouter();
        $kernel = new KernelWithMiddlewareGroupWeb($this->getApplication(), $router);

        $this->assertEquals([
            'web' => [],
        ], $router->getMiddlewareGroups());
        $this->assertEquals([
            'web' => [],
        ], $kernel->getMiddlewareGroups());
    }

    public function testKernelAppendMiddlewareToGroup()
    {
        $router = $this->getRouter();
        $kernel = new KernelWithMiddlewareGroupWeb($this->getApplication(), $router);

        $router->pushMiddlewareToGroup('web', MiddlewareA::class);
        $this->assertEquals([
            'web' => [
                MiddlewareA::class
            ]
        ], $router->getMiddlewareGroups());

        $kernel->appendMiddlewareToGroup('web', MiddlewareB::class);
        $this->assertEquals([
            'web' => [
                MiddlewareA::class,
                MiddlewareB::class
            ]
        ], $router->getMiddlewareGroups());
    }

    public function testKernelPrependMiddlewareToGroup()
    {
        $router = $this->getRouter();
        $kernel = new KernelWithMiddlewareGroupWeb($this->getApplication(), $router);
        $router->pushMiddlewareToGroup('web', MiddlewareA::class);
        $this->assertEquals([
            'web' => [
                MiddlewareA::class
            ]
        ], $router->getMiddlewareGroups());

        $kernel->prependMiddlewareToGroup('web', MiddlewareB::class);
        $this->assertEquals([
            'web' => [
                MiddlewareB::class,
                MiddlewareA::class,
            ]
        ], $router->getMiddlewareGroups());
    }

    public function testKernelAppendToMiddlewarePriority()
    {
        $router = $this->getRouter();
        $kernel = new KernelWithMiddlewareGroupWeb($this->getApplication(), $router);
        $router->pushMiddlewareToGroup('web', MiddlewareA::class);
        $this->assertEquals([
            'web' => [
                MiddlewareA::class,
            ]
        ], $router->getMiddlewareGroups());

        $kernel->appendToMiddlewarePriority(MiddlewareB::class);
        $this->assertEquals([
            'web' => [
                MiddlewareA::class,
            ],
        ], $router->getMiddlewareGroups());
    }

    public function testKernelPrependToMiddlewarePriority()
    {
        $router = $this->getRouter();
        $kernel = new KernelWithMiddlewareGroupWeb($this->getApplication(), $router);
        $router->pushMiddlewareToGroup('web', MiddlewareA::class);
        $this->assertEquals([
            'web' => [
                MiddlewareA::class,
            ]
        ], $router->getMiddlewareGroups());

        $kernel->prependToMiddlewarePriority(MiddlewareB::class);
        $this->assertEquals([
            'web' => [
                MiddlewareA::class,
            ],
        ], $router->getMiddlewareGroups());
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application
     */
    protected function getApplication()
    {
        return new Application;
    }

    /**
     * @return \Illuminate\Routing\Router
     */
    protected function getRouter()
    {
        return new Router(new Dispatcher);
    }
}

class KernelWithMiddlewareGroupWeb extends Kernel
{
    protected $middlewareGroups = [
        'web' => [],
    ];
}

class MiddlewareA
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}

class MiddlewareB
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}
