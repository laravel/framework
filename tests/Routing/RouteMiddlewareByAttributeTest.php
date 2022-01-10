<?php

namespace Illuminate\Tests\Routing;

use Exception;
use PHPUnit\Framework\TestCase;

class RouteMiddlewareByAttributeTest extends TestCase
{
    /** @var \Illuminate\Tests\Routing\fixtures\MiddlewareByAttributeController | null $controller */
    protected $controller = null;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->controller = app()->make('\Illuminate\Tests\Routing\fixtures\MiddlewareByAttributeController');
        } catch (Exception $e) {}
    }

    public function testControllerInstance()
    {
        $this->assertNotNull($this->controller);
    }

    public function testControllerMiddleware()
    {
        $middleware = collect($this->controller->getMiddleware())->pluck('middleware')->all();

        $this->assertEquals(['one', 'two:arg1,arg2'], $middleware);
    }
}
