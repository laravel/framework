<?php

namespace Illuminate\Tests\Utils;

use Illuminate\Concurrency\ProcessDriver;
use Illuminate\Foundation\Application;
use Illuminate\Process\Factory as ProcessFactory;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

#[RequiresOperatingSystem('Linux|DAR')]
class CustomExceptionWithMultipleArgTest extends TestCase
{
    protected function setUp(): void
    {
        $this->defineCacheRoutes(<<<PHP
            <?php
            use Illuminate\Support\Facades\Concurrency;
            use Illuminate\Support\Facades\Route;
            use Illuminate\Tests\Utils\ApiRequestException;

            Route::any('/custom-exception-with-mul-args', function () {
                return Concurrency::run([
                    fn () => throw new ApiRequestException('https://api.example.com', 400, 'Bad Request', 'Invalid payload'),
                    fn () => 'Task 2 completed',
                ]);
            });
        PHP);
        parent::setUp();
    }

    public function testFailCustomExceptionWithMulArgs()
    {
        $response = $this->get('/custom-exception-with-mul-args');

        $response->assertOk();
    }
}
