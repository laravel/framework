<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Routing\Controllers\Attributes\UseMiddleware;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class AttributeUseMiddlewareTest extends TestCase
{
    public function test_has_middleware_is_respected()
    {
        $route = Route::get('/', [UseMiddlewareTestController::class, 'stringInput']);
        $this->assertEquals($route->methodMiddleware(), ['all']);

        $route = Route::get('/', [HasMiddlewareTestController::class, 'arrayInput']);
        $this->assertEquals($route->methodMiddleware(), ['all', 'middleware_1']);

        $route = Route::get('/', [HasMiddlewareTestController::class, 'repeat']);
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
    public function array()
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
