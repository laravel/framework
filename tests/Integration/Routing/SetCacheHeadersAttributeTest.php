<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Routing\Attributes\Controllers\SetCacheHeaders;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class SetCacheHeadersAttributeTest extends TestCase
{
    public function test_attribute_middleware_is_respected(): void
    {
        $route = Route::get('/', [SetCacheHeadersAttributeController::class, 'public']);
        $this->assertEquals([
            'Illuminate\Http\Middleware\SetCacheHeaders:max_age=120;no-transform;s_maxage=60;',
        ], $route->controllerMiddleware());

        $route = Route::get('/', [SetCacheHeadersAttributeController::class, 'private']);
        $this->assertEquals([
            'Illuminate\Http\Middleware\SetCacheHeaders:private;max_age=120;etag',
        ], $route->controllerMiddleware());
    }
}

class SetCacheHeadersAttributeController
{
    #[SetCacheHeaders('max_age=120;no-transform;s_maxage=60;')]
    public function public(): void
    {
        // ...
    }

    #[SetCacheHeaders(['private', 'max_age' => 120, 'etag' => true])]
    public function private(): void
    {
        // ...
    }
}
