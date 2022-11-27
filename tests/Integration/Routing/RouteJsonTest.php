<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class RouteJsonTest extends TestCase
{
    public function testRouteJson()
    {
        Route::json('route', $json = ['foo' => 'bar']);

        $this->assertSame(json_encode($json), $this->get('/route')->getContent());
        $this->assertSame(200, $this->get('/route')->status());
    }

    public function testRouteJsonWithStatus()
    {
        Route::json('route', $json = ['foo' => 'bar'], 418);

        $this->assertSame(418, $this->get('/route')->status());
    }

    public function testRouteJsonWithHeaders()
    {
        Route::json('route', $json = ['foo' => 'bar'], 418, ['Framework' => 'Laravel']);

        $this->assertSame('Laravel', $this->get('/route')->headers->get('Framework'));
    }

    public function testRouteJsonOverloadingStatusWithHeaders()
    {
        Route::json('route', $json = ['foo' => 'bar'], ['Framework' => 'Laravel']);

        $this->assertSame('Laravel', $this->get('/route')->headers->get('Framework'));
    }

    public function testRouteJsonWithOptions()
    {
        Route::json('route', $json = ['float' => 8.0], 418, ['Framework' => 'Laravel'], JSON_PRESERVE_ZERO_FRACTION);

        $this->assertSame(json_encode($json, JSON_PRESERVE_ZERO_FRACTION), $this->get('/route')->getContent());
        $this->assertSame(JSON_PRESERVE_ZERO_FRACTION, $this->get('/route')->getEncodingOptions());
    }
}
