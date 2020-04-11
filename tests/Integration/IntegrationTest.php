<?php

namespace Illuminate\Tests\Integration;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class IntegrationTest extends TestCase
{
    public function test_simple_route_through_the_framework()
    {
        Route::get('/', function () {
            return 'Hello World';
        });

        $response = $this->get('/');

        $this->assertEquals('Hello World', $response->content());
    }
}
