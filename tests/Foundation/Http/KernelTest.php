<?php

namespace Illuminate\Tests\Foundation\Http;

use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Kernel;

class KernelTest extends TestCase
{
    public function testGetMiddlewareGroups()
    {
        $kernel = new Kernel($this->getApplication(), $this->getRouter());

        $this->assertEquals([], $kernel->getMiddlewareGroups());
    }

    /**
     * @return \Illuminate\Foundation\Application
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
