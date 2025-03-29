<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Exception;
use Illuminate\Concurrency\RedisDriver;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\RedisManager;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class RedisDriverTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
        m::close();
    }

    public function testRunMethodWithSingleTask()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded');
        }

        $factory = new RedisManager($this->app, 'phpredis', [
            'default' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'port' => env('REDIS_PORT', 6379),
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);

        $driver = new RedisDriver($factory, 'default', 'testing:concurrency:');

        // Test with a single task
        $result = $driver->run(function () {
            return 'Hello, World!';
        });

        $this->assertEquals(['Hello, World!'], $result);
    }

    public function testRunMethodWithMultipleTasks()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded');
        }

        $factory = new RedisManager($this->app, 'phpredis', [
            'default' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'port' => env('REDIS_PORT', 6379),
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);

        $driver = new RedisDriver($factory, 'default', 'testing:concurrency:');

        // Test with multiple tasks
        $result = $driver->run([
            function () {
                return 'Task 1';
            },
            function () {
                return 'Task 2';
            },
            function () {
                return 'Task 3';
            },
        ]);

        $this->assertEquals(['Task 1', 'Task 2', 'Task 3'], $result);
    }

    public function testDeferMethod()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded');
        }

        $factory = new RedisManager($this->app, 'phpredis', [
            'default' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'port' => env('REDIS_PORT', 6379),
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);

        $driver = new RedisDriver($factory, 'default', 'testing:concurrency:');

        // Test with a single deferred task
        $deferred = $driver->defer(function () {
            return 'Deferred Task';
        });

        $this->assertNotNull($deferred);
    }

    public function testErrorHandlingInRunMethod()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded');
        }

        $factory = new RedisManager($this->app, 'phpredis', [
            'default' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'port' => env('REDIS_PORT', 6379),
                'database' => 5,
                'timeout' => 0.5,
            ],
        ]);

        $driver = new RedisDriver($factory, 'default', 'testing:concurrency:');

        // Test error handling
        $this->expectException(Exception::class);

        $driver->run(function () {
            throw new Exception('Test exception');
        });
    }
} 