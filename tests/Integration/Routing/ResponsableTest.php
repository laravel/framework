<?php

namespace Illuminate\Tests\Integration\Routing;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Support\Responsable;

/**
 * @group integration
 */
class ResponsableTest extends TestCase
{
    public function testResponsableObjectsAreRendered()
    {
        Route::get('/responsable', function () {
            return new TestResponsableResponse;
        });

        $response = $this->get('/responsable');

        $this->assertEquals(201, $response->status());
        $this->assertSame('Taylor', $response->headers->get('X-Test-Header'));
        $this->assertSame('hello world', $response->getContent());
    }
}

class TestResponsableResponse implements Responsable
{
    public function toResponse($request)
    {
        return response('hello world', 201, ['X-Test-Header' => 'Taylor']);
    }
}
