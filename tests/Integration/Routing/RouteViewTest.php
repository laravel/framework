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
}
