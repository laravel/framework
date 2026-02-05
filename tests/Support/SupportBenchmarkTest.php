<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Benchmark;
use Illuminate\Support\BenchmarkTimeUnit;
use PHPUnit\Framework\TestCase;

class SupportBenchmarkTest extends TestCase
{
    public function testMeasure(): void
    {
        $this->assertIsNumeric(Benchmark::measure(fn () => 1 + 1));

        $this->assertIsArray(Benchmark::measure([
            'first' => fn () => 1 + 1,
            'second' => fn () => 2 + 2,
        ], 3));
    }

    public function testValue(): void
    {
        $this->assertIsArray(Benchmark::value(fn () => 1 + 1));
    }

    public function testTimedThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test exception');

        Benchmark::timed(
            fn () => throw new \RuntimeException('Test exception'),
        );
    }

    public function testTryTimedReturnsException()
    {
        [$result, $duration, $exception] = Benchmark::tryTimed(
            fn () => throw new \RuntimeException('Test exception'),
        );

        $this->assertNull($result);
        $this->assertGreaterThan(0, $duration);
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame('Test exception', $exception->getMessage());
    }

    public function testTryTimedReturnsResultWhenNoException()
    {
        [$result, $duration, $exception] = Benchmark::tryTimed(
            fn () => 'success',
            BenchmarkTimeUnit::Seconds
        );

        $this->assertSame('success', $result);
        $this->assertGreaterThan(0, $duration);
        $this->assertNull($exception);
    }

    public function testTimedAndTryTimedProduceSameDuration()
    {
        $callback = fn () => usleep(1000);

        [, $duration1] = Benchmark::timed($callback, BenchmarkTimeUnit::Microseconds);
        [, $duration2, $exception] = Benchmark::tryTimed($callback, BenchmarkTimeUnit::Microseconds);

        $this->assertEquals($duration1, $duration2, 0.1);
        $this->assertNull($exception);
    }

    public function testMacroable(): void
    {
        $macroName = __FUNCTION__;

        $this->assertFalse(Benchmark::hasMacro($macroName));

        // Register a macro to test
        Benchmark::macro($macroName, fn () => true);

        $this->assertTrue(Benchmark::hasMacro($macroName));
        $this->assertTrue(Benchmark::$macroName());
    }
}
