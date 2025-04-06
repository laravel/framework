<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Illuminate\Concurrency\ForkDriver;
use Illuminate\Support\Defer\DeferredCallback;
use Mockery;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use ReflectionProperty;
use Spatie\Fork\Fork;

#[RequiresOperatingSystem('Linux|DAR')]
class ForkDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Fork::class)) {
            // Define a mock Fork class for testing if not installed
            eval('namespace Spatie\Fork; class Fork { 
                public static function new() { 
                    return new self();
                }
                
                public function run(...$values) {
                    $results = [];
                    foreach ($values as $value) {
                        $results[] = $value();
                    }
                    return $results;
                }
            }');

            // Ensure it was created successfully
            $this->assertTrue(class_exists('Spatie\Fork\Fork'));
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRunWithSingleTask()
    {
        $driver = new ForkDriver();

        $results = $driver->run(fn () => 42);

        $this->assertEquals([0 => 42], $results);
    }

    public function testRunWithMultipleNumericKeyTasks()
    {
        $driver = new ForkDriver();

        $results = $driver->run([
            fn () => 1,
            fn () => 2,
            fn () => 3,
        ]);

        $this->assertEquals([0 => 1, 1 => 2, 2 => 3], $results);
    }

    public function testRunWithAssociativeArrayTasks()
    {
        $driver = new ForkDriver();

        $results = $driver->run([
            'first' => fn () => 10,
            'second' => fn () => 20,
            'third' => fn () => 30,
        ]);

        $this->assertEquals([
            'first' => 10,
            'second' => 20,
            'third' => 30,
        ], $results);
    }

    public function testRunWithMixedKeyTypes()
    {
        $driver = new ForkDriver();

        $results = $driver->run([
            0 => fn () => 'zero',
            'one' => fn () => 'one',
            2 => fn () => 'two',
        ]);

        $this->assertEquals([
            0 => 'zero',
            'one' => 'one',
            2 => 'two',
        ], $results);
    }

    public function testRunWithNonSequentialNumericKeys()
    {
        $driver = new ForkDriver();

        $results = $driver->run([
            5 => fn () => 'five',
            10 => fn () => 'ten',
            15 => fn () => 'fifteen',
        ]);

        $this->assertEquals([
            5 => 'five',
            10 => 'ten',
            15 => 'fifteen',
        ], $results);
    }

    public function testDeferExecutesRunWhenCallbackIsInvoked()
    {
        // Create a mock ForkDriver that will verify run() is called
        $driver = Mockery::mock(ForkDriver::class)->makePartial();
        $driver->shouldReceive('run')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn(['result']);

        $tasks = [fn () => 'task'];
        $deferred = $driver->defer($tasks);

        $this->assertInstanceOf(DeferredCallback::class, $deferred);

        // Invoke the callback to ensure it calls run()
        $refProperty = new ReflectionProperty($deferred, 'callback');
        $refProperty->setAccessible(true);
        $callback = $refProperty->getValue($deferred);
        $result = $callback();

        $this->assertEquals(['result'], $result);
    }

    public function testDeferWithSingleClosureTask()
    {
        $driver = Mockery::mock(ForkDriver::class)->makePartial();
        $task = fn () => 'single task';

        $driver->shouldReceive('run')
            ->once()
            ->with(Mockery::type(\Closure::class))
            ->andReturn([0 => 'single task']);

        $deferred = $driver->defer($task);

        $this->assertInstanceOf(DeferredCallback::class, $deferred);

        // Invoke the callback
        $refProperty = new ReflectionProperty($deferred, 'callback');
        $refProperty->setAccessible(true);
        $callback = $refProperty->getValue($deferred);
        $result = $callback();

        $this->assertEquals([0 => 'single task'], $result);
    }

    public function testDeferReturnsDeferredCallbackInstance()
    {
        $driver = new ForkDriver();

        $deferred = $driver->defer(fn () => true);

        $this->assertInstanceOf(DeferredCallback::class, $deferred);
    }
}
