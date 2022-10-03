<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class VerifyEnvironmentTest extends TestCase
{
    public function testNotAllowedEnvironment()
    {
        Route::get('/test', function () {
            return 'My test code...';
        })->environment('staging');

        $response = $this->get('/test');
        $response->assertStatus(404);

        Route::get('/test', function () {
            return 'My test code...';
        })->environment(['staging', 'local']);

        $response = $this->get('/test');
        $response->assertStatus(404);
    }

    public function testAllowedEnvironment()
    {
        Route::get('/test', function () {
            return 'My test code...';
        })->environment('testing');

        $response = $this->get('/test');
        $response->assertStatus(200);

        Route::get('/test', function () {
            return 'My test code...';
        })->environment(['testing', 'local']);

        $response = $this->get('/test');
        $response->assertStatus(200);
    }
}
