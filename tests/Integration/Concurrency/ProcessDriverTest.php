<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Exception;
use Illuminate\Concurrency\ProcessDriver;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\Pool;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Defer\DeferredCallback;
use Mockery;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

/**
 * Custom ProcessDriver that overrides base_path call for testing.
 */
class TestableProcessDriver extends ProcessDriver
{
    protected function getBasePath(): string
    {
        return '/path/to/base';
    }
}

#[RequiresOperatingSystem('Linux|DAR')]
class ProcessDriverTest extends TestCase
{
    private TestableProcessDriver $driver;
    private ProcessFactory|MockInterface $processFactory;
    private Pool|MockInterface $pool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pool = Mockery::mock(Pool::class);

        $this->processFactory = Mockery::mock(ProcessFactory::class);
        $this->processFactory->shouldReceive('pool')
            ->andReturnUsing(function ($callback) {
                $callback($this->pool);

                return $this->pool;
            });

        $this->driver = new TestableProcessDriver($this->processFactory);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRunExecutesTasksInProcessPool()
    {
        $this->pool->shouldReceive('as')
            ->with(0)
            ->andReturnSelf();
        $this->pool->shouldReceive('as')
            ->with(1)
            ->andReturnSelf();
        $this->pool->shouldReceive('path')
            ->andReturnSelf();
        $this->pool->shouldReceive('env')
            ->andReturnSelf();
        $this->pool->shouldReceive('command')
            ->andReturnSelf();
        $this->pool->shouldReceive('start')
            ->andReturnSelf();

        $processResult1 = Mockery::mock(ProcessResult::class);
        $processResult1->shouldReceive('failed')->andReturn(false);
        $processResult1->shouldReceive('output')->andReturn(json_encode([
            'successful' => true,
            'result' => serialize(2),
        ]));

        $processResult2 = Mockery::mock(ProcessResult::class);
        $processResult2->shouldReceive('failed')->andReturn(false);
        $processResult2->shouldReceive('output')->andReturn(json_encode([
            'successful' => true,
            'result' => serialize(4),
        ]));

        $results = Mockery::mock();
        $results->shouldReceive('collect')->andReturn(collect([
            0 => $processResult1,
            1 => $processResult2,
        ]));

        $this->pool->shouldReceive('wait')->andReturn($results);

        $output = $this->driver->run([
            fn () => 1 + 1,
            fn () => 2 + 2,
        ]);

        $this->assertEquals([2, 4], $output);
    }

    public function testRunFailsWhenProcessFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Concurrent process failed with exit code [1]. Message: Error output');

        $this->pool->shouldReceive('as')->andReturnSelf();
        $this->pool->shouldReceive('path')->andReturnSelf();
        $this->pool->shouldReceive('env')->andReturnSelf();
        $this->pool->shouldReceive('command')->andReturnSelf();
        $this->pool->shouldReceive('start')->andReturnSelf();

        $processResult = Mockery::mock(ProcessResult::class);
        $processResult->shouldReceive('failed')->andReturn(true);
        $processResult->shouldReceive('exitCode')->andReturn(1);
        $processResult->shouldReceive('errorOutput')->andReturn('Error output');

        $results = Mockery::mock();
        $results->shouldReceive('collect')->andReturn(collect([
            0 => $processResult,
        ]));

        $this->pool->shouldReceive('wait')->andReturn($results);

        $this->driver->run([
            fn () => 1 + 1,
        ]);
    }

    public function testRunFailsWhenTaskThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Task exception');

        $this->pool->shouldReceive('as')->andReturnSelf();
        $this->pool->shouldReceive('path')->andReturnSelf();
        $this->pool->shouldReceive('env')->andReturnSelf();
        $this->pool->shouldReceive('command')->andReturnSelf();
        $this->pool->shouldReceive('start')->andReturnSelf();

        $processResult = Mockery::mock(ProcessResult::class);
        $processResult->shouldReceive('failed')->andReturn(false);
        $processResult->shouldReceive('output')->andReturn(json_encode([
            'successful' => false,
            'exception' => Exception::class,
            'message' => 'Task exception',
            'parameters' => [],
        ]));

        $results = Mockery::mock();
        $results->shouldReceive('collect')->andReturn(collect([
            0 => $processResult,
        ]));

        $this->pool->shouldReceive('wait')->andReturn($results);

        $this->driver->run([
            fn () => throw new Exception('Task exception'),
        ]);
    }

    public function testDeferCreatesBackgroundProcess()
    {
        $this->processFactory->shouldReceive('path')
            ->with('/path/to/base')
            ->andReturnSelf();
        $this->processFactory->shouldReceive('env')
            ->andReturnSelf();
        $this->processFactory->shouldReceive('run')
            ->with(Mockery::pattern('/.*2>&1 &/'))
            ->andReturn('');

        $deferred = $this->driver->defer(fn () => 1 + 1);

        $this->assertInstanceOf(DeferredCallback::class, $deferred);
    }

    /**
     * @test
     */
    public function deferWithMultipleTasksCreatesMultipleBackgroundProcesses()
    {
        // تنظیم انتظارات برای دو فراخوانی متوالی processFactory->path و غیره
        $this->processFactory->shouldReceive('path')
            ->with('/path/to/base')
            ->andReturnSelf()
            ->times(2);

        $this->processFactory->shouldReceive('env')
            ->andReturnSelf()
            ->times(2);

        $this->processFactory->shouldReceive('run')
            ->with(Mockery::pattern('/.*2>&1 &/'))
            ->andReturn('')
            ->times(2);

        // فراخوانی defer با آرایه‌ای از تسک‌ها
        $deferred = $this->driver->defer([
            fn () => 1 + 1,
            fn () => 2 + 2,
        ]);

        $this->assertInstanceOf(DeferredCallback::class, $deferred);

        // فراخوانی دستی callback برای اجرای foreach
        $reflection = new \ReflectionProperty($deferred, 'callback');
        $reflection->setAccessible(true);
        $callback = $reflection->getValue($deferred);
        $callback();
    }

    /**
     * @test
     */
    public function theGetBasePathMethodReturnsTheCorrectPath()
    {
        $reflection = new \ReflectionClass($this->driver);
        $method = $reflection->getMethod('getBasePath');
        $method->setAccessible(true);

        $result = $method->invoke($this->driver);

        $this->assertEquals('/path/to/base', $result);
    }

    /**
     * @test
     */
    public function runHandlesResultsCorrectly()
    {
        $this->pool->shouldReceive('as')
            ->with(0)
            ->andReturnSelf();
        $this->pool->shouldReceive('path')
            ->andReturnSelf();
        $this->pool->shouldReceive('env')
            ->andReturnSelf();
        $this->pool->shouldReceive('command')
            ->andReturnSelf();
        $this->pool->shouldReceive('start')
            ->andReturnSelf();

        $processResult = Mockery::mock(ProcessResult::class);
        $processResult->shouldReceive('failed')->andReturn(false);
        $processResult->shouldReceive('output')->andReturn(json_encode([
            'successful' => true,
            'result' => serialize('test result'),
        ]));

        $results = Mockery::mock();
        $results->shouldReceive('collect')->andReturn(collect([
            0 => $processResult,
        ]));

        $this->pool->shouldReceive('wait')->andReturn($results);

        $output = $this->driver->run(fn () => 'test value');

        $this->assertEquals(['test result'], array_values($output));
    }
}
