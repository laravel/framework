<?php

namespace Illuminate\Tests\Foundation\Http;

use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    public function testGetMiddlewareGroups()
    {
        $kernel = new Kernel($this->getApplication(), $this->getRouter());

        $this->assertEquals([], $kernel->getMiddlewareGroups());
    }

    public function testGetRouteMiddleware()
    {
        $kernel = new Kernel($this->getApplication(), $this->getRouter());

        $this->assertEquals([], $kernel->getRouteMiddleware());
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
