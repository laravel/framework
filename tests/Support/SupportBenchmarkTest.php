<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Benchmark;
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
