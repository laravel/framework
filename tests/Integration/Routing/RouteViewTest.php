<?php

namespace Illuminate\Tests\Integration\Routing;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

/**
 * @group integration
 */
class RouteViewTest extends TestCase
{
    public function test_route_view()
    {
        Route::view('route', 'view', ['foo' => 'bar']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertContains('Test bar', $this->get('/route')->getContent());
    }

    public function test_route_view_with_params()
    {
        Route::view('route/{param}/{param2?}', 'view', ['foo' => 'bar']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertContains('Test bar', $this->get('/route/value1/value2')->getContent());
        $this->assertContains('Test bar', $this->get('/route/value1')->getContent());
    }

    public function test_route_view_directory()
    {
        Route::viewDir('route', '/', ['foo' => 'bar']);

        View::addLocation(__DIR__ . '/Fixtures');

        $this->assertContains('Index bar', $this->get('/route')->getContent());
        $this->assertContains('Test bar', $this->get('/route/view')->getContent());
        $this->assertContains('Sub-index bar', $this->get('/route/sub')->getContent());
        $this->assertContains('Sub-test bar', $this->get('/route/sub/test')->getContent());
        $this->assertEquals(404, $this->get('/a/route/sub/nonexistent')->getStatusCode());
    }
}
