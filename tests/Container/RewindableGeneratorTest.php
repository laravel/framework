<?php

namespace Illuminate\Tests\Container;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\RewindableGenerator;

class RewindableGeneratorTest extends TestCase
{
    public function testCountUsesProvidedValue()
    {
        $generator = new RewindableGenerator(function () {
            yield 'foo';
        }, 999);

        $this->assertSame(999, count($generator));
    }

    public function testCountUsesProvidedValueAsCallback()
    {
        $called = 0;

        $generator = new RewindableGenerator(function () {
            yield 'foo';
        }, function () use (&$called) {
            $called++;

            return 500;
        });

        // the count callback is called lazily
        $this->assertSame(0, $called);

        $this->assertCount(500, $generator);

        count($generator);

        // the count callback is called only once
        $this->assertSame(1, $called);
    }
}
