<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Routing\Controllers\Attributes\UseMiddleware;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class AttributeUseMiddlewareTest extends TestCase
{
    public function test_attribute_use_middleware()
    {
        $route = Route::get('/', [UseMiddlewareTestController::class, 'stringInput']);
        $this->assertEquals($route->methodMiddleware(), ['all']);

        $route = Route::get('/', [UseMiddlewareTestController::class, 'arrayInput']);
        $this->assertEquals($route->methodMiddleware(), ['all', 'middleware_1']);

        $route = Route::get('/', [UseMiddlewareTestController::class, 'repeat']);
        $this->assertEquals($route->methodMiddleware(), ['all', 'middleware_1', 'middleware_2', 'middleware_3']);
    }
}

class UseMiddlewareTestController
{
    #[UseMiddleware('all')]
    public function stringInput()
    {
        //
    }

    #[UseMiddleware(['all', 'middleware_1'])]
    public function arrayInput()
    {
        //
    }

    #[UseMiddleware(['all'])]
    #[UseMiddleware('middleware_1')]
    #[UseMiddleware(['middleware_2', 'middleware_3'])]
    public function repeat()
    {
        //
    }
}
