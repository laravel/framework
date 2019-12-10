<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class RouteGoneTest extends TestCase
{
    public function testRouteGoneReturnsResponse()
    {
        Route::gone('old_page');

        $response = $this->get('/old_page');
        $this->assertEquals(410, $response->getStatusCode());
        $this->assertEquals('Gone', $response->getContent());
    }

    public function testRouteGoneReturnsView()
    {
        Route::gone('old_page');

        View::addNamespace('errors', __DIR__.'/Fixtures/errors');

        $response = $this->get('/old_page');
        $this->assertEquals(410, $response->getStatusCode());
        $this->assertStringContainsString('Gone', $response->getContent());
    }

//    public function testRouteViewWithParams()
//    {
//        Route::view('route/{param}/{param2?}', 'view', ['foo' => 'bar']);
//
//        View::addLocation(__DIR__.'/Fixtures');
//
//        $this->assertStringContainsString('Test bar', $this->get('/route/value1/value2')->getContent());
//        $this->assertStringContainsString('Test bar', $this->get('/route/value1')->getContent());
//    }
}
