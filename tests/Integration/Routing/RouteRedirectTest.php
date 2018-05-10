<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class RouteRedirectTest extends TestCase
{
    public function test_route_redirect()
    {
        Route::redirect('from', 'to', 301);

        $response = $this->get('/from');
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('to', $response->headers->get('Location'));
    }

    public function test_route_redirect_with_params()
    {
        Route::redirect('from/{param}/{param2?}', 'to', 301);

        $response = $this->get('/from/value1/value2');
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('to', $response->headers->get('Location'));

        $response = $this->get('/from/value1');
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('to', $response->headers->get('Location'));
    }
}
