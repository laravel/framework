<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class SimpleRouteTest extends TestCase
{
    public function testSimpleRouteThroughTheFramework()
    {
        Route::get('/', function () {
            return 'Hello World';
        });

        $response = $this->get('/');

        $this->assertSame('Hello World', $response->content());
    }
}
