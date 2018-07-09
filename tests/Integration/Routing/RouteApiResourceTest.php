<?php

namespace Illuminate\Tests\Integration\Routing;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Tests\Integration\Routing\Fixtures\ApiResourceTaskController;
use Illuminate\Tests\Integration\Routing\Fixtures\ApiResourceTestController;

/**
 * @group integration
 */
class RouteApiResourceTest extends TestCase
{
    public function test_api_resource()
    {
        Route::apiResource('tests', ApiResourceTestController::class);

        $response = $this->get('/tests');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m index', $response->getContent());

        $response = $this->post('/tests');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m store', $response->getContent());

        $response = $this->get('/tests/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m show', $response->getContent());

        $response = $this->put('/tests/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m update', $response->getContent());
        $response = $this->patch('/tests/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m update', $response->getContent());

        $response = $this->delete('/tests/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m destroy', $response->getContent());
    }

    public function test_api_resource_with_only()
    {
        Route::apiResource('tests', ApiResourceTestController::class)->only(['index', 'store']);

        $response = $this->get('/tests');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m index', $response->getContent());

        $response = $this->post('/tests');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m store', $response->getContent());

        $this->assertEquals(404, $this->get('/tests/1')->getStatusCode());
        $this->assertEquals(404, $this->put('/tests/1')->getStatusCode());
        $this->assertEquals(404, $this->patch('/tests/1')->getStatusCode());
        $this->assertEquals(404, $this->delete('/tests/1')->getStatusCode());
    }

    public function test_api_resources()
    {
        Route::apiResources([
            'tests' => ApiResourceTestController::class,
            'tasks' => ApiResourceTaskController::class,
        ]);

        $response = $this->get('/tests');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m index', $response->getContent());

        $response = $this->post('/tests');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m store', $response->getContent());

        $response = $this->get('/tests/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m show', $response->getContent());

        $response = $this->put('/tests/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m update', $response->getContent());
        $response = $this->patch('/tests/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m update', $response->getContent());

        $response = $this->delete('/tests/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m destroy', $response->getContent());

        /////////////////////
        $response = $this->get('/tasks');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m index tasks', $response->getContent());

        $response = $this->post('/tasks');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m store tasks', $response->getContent());

        $response = $this->get('/tasks/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m show tasks', $response->getContent());

        $response = $this->put('/tasks/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m update tasks', $response->getContent());
        $response = $this->patch('/tasks/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m update tasks', $response->getContent());

        $response = $this->delete('/tasks/1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('I`m destroy tasks', $response->getContent());
    }
}
