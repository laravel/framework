<?php

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

        View::addLocation(__DIR__);

        $this->assertContains('Test bar', $this->get('/route')->getContent());
    }
}
