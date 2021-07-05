<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\Idempotent;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

class IdempotentRequestTest extends TestbenchTestCase
{
    public function testValidIdempotentRoutes()
    {
        Route::post('/', function () {
            return request()->name;
        })->middleware(Idempotent::class);

        $response = $this->post(
            '/',
            [
                'name' => 'laravel'
            ],
            [
                'Accept' => 'application/json',
                'Idempotency-Key' => Str::uuid()
            ]
        );

        $this->assertSame('laravel', $response->content());
        $response->assertStatus(200);
    }

    public function testDuplicateIdempotentRoutes()
    {
        Route::post('/', function () {
            return request()->name;
        })->middleware(Idempotent::class);

        $uuid = Str::uuid();

        $response = $this->post(
            '/',
            [
                'name' => 'laravel'
            ],
            [
                'Accept' => 'application/json',
                'Idempotency-Key' => $uuid
            ]
        );

        $this->assertSame('laravel', $response->content());

        $response = $this->post(
            '/',
            [
                'name' => 'laravel'
            ],
            [
                'Accept' => 'application/json',
                'Idempotency-Key' => $uuid
            ]
        );

        $response->assertStatus(422);
    }

    public function testEmptyIdempotencyKey()
    {
        Route::post('/', function () {
            return request()->name;
        })->middleware(Idempotent::class);

        $response = $this->post(
            '/',
            [
                'name' => 'laravel'
            ],
            [
                'Accept' => 'application/json',
            ]
        );

        $response->assertStatus(400);
    }
}
