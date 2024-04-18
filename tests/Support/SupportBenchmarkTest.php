<?php

declare(strict_types=1);

namespace Illuminate\Tests\Support;

use Illuminate\Support\Benchmark;
use PHPUnit\Framework\TestCase;

class SupportBenchmarkTest extends TestCase
{
    public function testMeasureWithClosure(): void
    {
        $closure = static function () {
            // Some operation to benchmark
            $arr = range(1, 1000);
            array_sum($arr);
        };

        $result = Benchmark::measure($closure, 10);

        $this->assertIsFloat($result);
    }

    public function testMeasureWithArray(): void
    {
        $callbacks = [
            function () {
                // Some operation to benchmark
                $arr = range(1, 1000);
                array_sum($arr);
            },
            function () {
                // Another operation to benchmark
                $arr = range(1, 1000);
                array_product($arr);
            },
        ];

        $result = Benchmark::measure($callbacks, 10);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testMeasureWithException(): void
    {
        $this->expectException(\Exception::class);

        $callback = fn () => throw new \Exception('Test exception');
        Benchmark::measure($callback, 2);
    }

    public function testValueWithClosure(): void
    {
        $closure = static function () {
            $arr = range(1, 1000);
            array_sum($arr);
        };

        $result = Benchmark::value($closure);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertIsFloat($result[1]);
    }

    public function testValueWithException(): void
    {
        $this->expectException(\Exception::class);

        $callback = fn () => throw new \Exception('Test exception');
        Benchmark::value($callback);
    }

    public function testDDWithCallable(): void
    {
        $callable = 'strlen';

        $this->expectException(\Error::class);
        Benchmark::dd($callable, 10);
    }

    public function testDDWithException(): void
    {
        $this->expectException(\Exception::class);

        $callback = fn () => throw new \Exception('Test exception');
        Benchmark::dd($callback);
    }
}
