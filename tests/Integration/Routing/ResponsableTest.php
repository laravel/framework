<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class ResponsableTest extends TestCase
{
    public function test_responsable_objects_are_rendered()
    {
        Route::get('/responsable', function () {
            return new TestResponsableResponse();
        });

        $response = $this->get('/responsable');

        $this->assertEquals(201, $response->status());
        $this->assertEquals('Taylor', $response->headers->get('X-Test-Header'));
        $this->assertEquals('hello world', $response->getContent());
    }
}

class TestResponsableResponse implements Responsable
{
    public function toResponse($request)
    {
        return response('hello world', 201, ['X-Test-Header' => 'Taylor']);
    }
}
