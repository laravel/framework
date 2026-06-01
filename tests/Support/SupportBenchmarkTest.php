<?php

namespace Illuminate\Support {
    function dd(mixed ...$vars): never
    {
        throw new \Illuminate\Tests\Support\BenchmarkDdCapturedException($vars[0]);
    }
}

namespace Illuminate\Tests\Support {
    use Illuminate\Support\Benchmark;
    use PHPUnit\Framework\TestCase;

    class BenchmarkDdCapturedException extends \RuntimeException
    {
        public function __construct(public readonly mixed $result)
        {
            parent::__construct();
        }
    }

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

            Benchmark::macro($macroName, fn () => true);

            $this->assertTrue(Benchmark::hasMacro($macroName));
            $this->assertTrue(Benchmark::$macroName());
        }

        public function testDdWithSingleClosure(): void
        {
            try {
                Benchmark::dd(fn () => 1 + 1);
                $this->fail('Expected BenchmarkDdCapturedException to be thrown');
            } catch (BenchmarkDdCapturedException $e) {
                $this->assertMatchesRegularExpression('/^\d+\.\d{3}ms$/', $e->result);
            }
        }

        public function testDdWithArray(): void
        {
            try {
                Benchmark::dd([
                    'first' => fn () => 1 + 1,
                    'second' => fn () => 2 + 2,
                ]);
                $this->fail('Expected BenchmarkDdCapturedException to be thrown');
            } catch (BenchmarkDdCapturedException $e) {
                $this->assertIsArray($e->result);
                $this->assertArrayHasKey('first', $e->result);
                $this->assertArrayHasKey('second', $e->result);
                $this->assertMatchesRegularExpression('/^\d+\.\d{3}ms$/', $e->result['first']);
                $this->assertMatchesRegularExpression('/^\d+\.\d{3}ms$/', $e->result['second']);
            }
        }

        public function testDdWithIterations(): void
        {
            try {
                Benchmark::dd(fn () => 1 + 1, iterations: 5);
                $this->fail('Expected BenchmarkDdCapturedException to be thrown');
            } catch (BenchmarkDdCapturedException $e) {
                $this->assertMatchesRegularExpression('/^\d+\.\d{3}ms$/', $e->result);
            }
        }
    }
}
