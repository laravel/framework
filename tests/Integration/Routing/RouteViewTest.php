<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Tests\Integration\Routing\Fixtures\DataProvider;
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
        $this->assertSame(200, $this->get('/route')->status());
    }

    public function testRouteViewDataProvider()
    {
        Route::view('route/{a}/{b}', 'view', [DataProvider::class, 'data']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertStringContainsString('Test 123', $this->get('/route/1/23')->getContent());
        $this->assertSame(200, $this->get('/route/1/23')->status());
    }

    public function testRouteViewWithParams()
    {
        Route::view('route/{param}/{param2?}', 'view', ['foo' => 'bar']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertStringContainsString('Test bar', $this->get('/route/value1/value2')->getContent());
        $this->assertStringContainsString('Test bar', $this->get('/route/value1')->getContent());
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
