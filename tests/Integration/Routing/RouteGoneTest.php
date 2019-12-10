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
    public function testRouteGoneReturnsResponseWhenNoViewExists()
    {
        Route::gone('old_page');

        $response = $this->get('/old_page');
        $this->assertEquals(410, $response->getStatusCode());
        $this->assertStringNotContainsString('html', $response->getContent());
        $this->assertEquals('Gone', $response->getContent());
    }

    public function testRouteGoneReturnsViewWhenViewExists()
    {
        Route::gone('old_page');

        View::addNamespace('errors', __DIR__.'/Fixtures/errors');

        $response = $this->get('/old_page');
        $this->assertEquals(410, $response->getStatusCode());
        $this->assertStringContainsString('html', $response->getContent());
        $this->assertStringContainsString('Gone', $response->getContent());
    }

    public function testRouteGoneWithParamsReturnsResponseWhenNoViewExists()
    {
        Route::gone('old_page/{param}/{param2?}');

        $response = $this->get('/old_page/foo');
        $this->assertEquals(410, $response->getStatusCode());
        $this->assertStringNotContainsString('html', $response->getContent());
        $this->assertStringContainsString('Gone', $response->getContent());
    }

    public function testRouteGoneWithParamsReturnsReturnsViewWhenViewExists()
    {
        Route::gone('old_page/{param}/{param2?}');

        View::addNamespace('errors', __DIR__.'/Fixtures/errors');

        $response = $this->get('/old_page/bar');
        $this->assertEquals(410, $response->getStatusCode());
        $this->assertStringContainsString('html', $response->getContent());
        $this->assertStringContainsString('Gone', $response->getContent());
    }
}
