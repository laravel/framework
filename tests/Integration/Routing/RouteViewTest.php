<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;

class RouteViewTest extends TestCase
{
    public function testRouteView()
    {
        Route::view('route', 'view', ['foo' => 'bar']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertStringContainsString('Test bar', $this->get('/route')->getContent());
        $this->assertSame(200, $this->get('/route')->status());
    }

    public function testRouteViewWithParams()
    {
        Route::view('route/{param}/{param2?}', 'view', ['foo' => 'bar']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertStringContainsString('Test bar', $this->get('/route/value1/value2')->getContent());
        $this->assertStringContainsString('Test bar', $this->get('/route/value1')->getContent());

        $this->assertEquals('value1', $this->get('/route/value1/value2')->viewData('param'));
        $this->assertEquals('value2', $this->get('/route/value1/value2')->viewData('param2'));
    }

    public function testRouteViewWithStatus()
    {
        Route::view('route', 'view', ['foo' => 'bar'], 418);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertSame(418, $this->get('/route')->status());
    }

    public function testRouteViewWithHeaders()
    {
        Route::view('route', 'view', ['foo' => 'bar'], 418, ['Framework' => 'Laravel']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertSame('Laravel', $this->get('/route')->headers->get('Framework'));
    }

    public function testRouteViewOverloadingStatusWithHeaders()
    {
        Route::view('route', 'view', ['foo' => 'bar'], ['Framework' => 'Laravel']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertSame('Laravel', $this->get('/route')->headers->get('Framework'));
    }
}
