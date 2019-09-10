<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class RouteViewTest extends TestCase
{
    public function testRouteView()
    {
        Route::view('route', 'view', ['foo' => 'bar']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertStringContainsString('Test bar', $this->get('/route')->getContent());
    }

    public function testRouteViewWithParams()
    {
        Route::view('route/{param}/{param2?}', 'view', ['foo' => 'bar']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertStringContainsString('Test bar', $this->get('/route/value1/value2')->getContent());
        $this->assertStringContainsString('Test bar', $this->get('/route/value1')->getContent());
    }
}
