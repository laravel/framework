<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Routing\Attributes\Controllers\Idempotent;
use Illuminate\Routing\Middleware\Idempotent as IdempotentMiddleware;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class IdempotentMiddlewareAttributeTest extends TestCase
{
    public function testAttributeExpandsToExpectedMiddlewareString()
    {
        $route = Route::post('/orders', [IdempotentAttributeTestController::class, 'store']);

        $this->assertEquals([
            IdempotentMiddleware::class.':86400,1,user,Idempotency-Key',
        ], $route->controllerMiddleware());
    }

    public function testAttributePassesCustomOptions()
    {
        $route = Route::post('/orders', [IdempotentAttributeCustomTestController::class, 'store']);

        $this->assertEquals([
            IdempotentMiddleware::class.':3600,0,ip,X-Idempotency-Key',
        ], $route->controllerMiddleware());
    }

    public function testClassLevelAttributeAppliesToAllMethods()
    {
        $storeRoute = Route::post('/orders', [IdempotentAttributeClassLevelTestController::class, 'store']);
        $updateRoute = Route::put('/orders/{id}', [IdempotentAttributeClassLevelTestController::class, 'update']);

        $this->assertEquals([
            IdempotentMiddleware::class.':86400,1,user,Idempotency-Key',
        ], $storeRoute->controllerMiddleware());

        $this->assertEquals([
            IdempotentMiddleware::class.':86400,1,user,Idempotency-Key',
        ], $updateRoute->controllerMiddleware());
    }

    public function testMethodLevelAttributeStacksWithClassLevel()
    {
        $storeRoute = Route::post('/orders', [IdempotentAttributeMethodOverrideTestController::class, 'store']);
        $updateRoute = Route::put('/orders/{id}', [IdempotentAttributeMethodOverrideTestController::class, 'update']);

        $this->assertEquals([
            IdempotentMiddleware::class.':86400,1,user,Idempotency-Key',
            IdempotentMiddleware::class.':3600,1,ip,X-Idempotency-Key',
        ], $storeRoute->controllerMiddleware());

        $this->assertEquals([
            IdempotentMiddleware::class.':86400,1,user,Idempotency-Key',
        ], $updateRoute->controllerMiddleware());
    }

    public function testOnlyOptionFiltersMethods()
    {
        $storeRoute = Route::post('/orders', [IdempotentAttributeOnlyTestController::class, 'store']);
        $updateRoute = Route::put('/orders/{id}', [IdempotentAttributeOnlyTestController::class, 'update']);

        $this->assertEquals([
            IdempotentMiddleware::class.':86400,1,user,Idempotency-Key',
        ], $storeRoute->controllerMiddleware());

        $this->assertEquals([], $updateRoute->controllerMiddleware());
    }

    public function testExceptOptionFiltersMethods()
    {
        $storeRoute = Route::post('/orders', [IdempotentAttributeExceptTestController::class, 'store']);
        $updateRoute = Route::put('/orders/{id}', [IdempotentAttributeExceptTestController::class, 'update']);

        $this->assertEquals([], $storeRoute->controllerMiddleware());

        $this->assertEquals([
            IdempotentMiddleware::class.':86400,1,user,Idempotency-Key',
        ], $updateRoute->controllerMiddleware());
    }
}

class IdempotentAttributeTestController
{
    #[Idempotent]
    public function store(): void
    {
    }
}

#[Idempotent(ttl: 3600, required: false, scope: 'ip', header: 'X-Idempotency-Key')]
class IdempotentAttributeCustomTestController
{
    public function store(): void
    {
    }
}

#[Idempotent]
class IdempotentAttributeClassLevelTestController
{
    public function store(): void
    {
    }

    public function update(): void
    {
    }
}

#[Idempotent]
class IdempotentAttributeMethodOverrideTestController
{
    #[Idempotent(ttl: 3600, scope: 'ip', header: 'X-Idempotency-Key')]
    public function store(): void
    {
    }

    public function update(): void
    {
    }
}

class IdempotentAttributeOnlyTestController
{
    #[Idempotent(only: ['store'])]
    public function store(): void
    {
    }

    public function update(): void
    {
    }
}

#[Idempotent(except: ['store'])]
class IdempotentAttributeExceptTestController
{
    public function store(): void
    {
    }

    public function update(): void
    {
    }
}
