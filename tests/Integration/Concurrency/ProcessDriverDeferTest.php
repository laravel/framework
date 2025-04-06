<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Support\Defer\DeferredCallback;
use Mockery;
use Orchestra\Testbench\TestCase;

/**
 * @group full-coverage
 */
class ProcessDriverDeferTest extends TestCase
{
    public function testTheDriverCanDeferMultipleTasks()
    {
        // Set up the specific test state
        $processFactory = Mockery::mock(ProcessFactory::class);

        // Set up precise expectations to match the source code
        $processFactory->shouldReceive('path')
            ->times(2)
            ->andReturnSelf();

        $processFactory->shouldReceive('env')
            ->withArgs(function ($env) {
                return isset($env['LARAVEL_INVOKABLE_CLOSURE']) && is_string($env['LARAVEL_INVOKABLE_CLOSURE']);
            })
            ->times(2)
            ->andReturnSelf();

        $processFactory->shouldReceive('run')
            ->with(Mockery::pattern('/.*2>&1 &/'))
            ->times(2)
            ->andReturn('');

        // Create a class that overrides the base_path method
        $driver = new class($processFactory) extends \Illuminate\Concurrency\ProcessDriver
        {
            protected function getBasePath(): string
            {
                return '/test/path';
            }
        };

        // Call defer with an array of tasks
        $deferred = $driver->defer([
            fn () => 'task1',
            fn () => 'task2',
        ]);

        $this->assertInstanceOf(DeferredCallback::class, $deferred);

        // Execute the callback to get full code coverage
        $refProperty = new \ReflectionProperty($deferred, 'callback');
        $refProperty->setAccessible(true);
        $callback = $refProperty->getValue($deferred);
        $callback();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
