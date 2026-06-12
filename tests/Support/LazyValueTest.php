<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\LazyValue;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LazyValueTest extends TestCase
{
    public function testCallbackIsNotEvaluatedUntilValueIsRequested()
    {
        $evaluated = false;
        $lazyValue = new LazyValue(function () use (&$evaluated) {
            $evaluated = true;

            return 'Totwell';
        });

        $this->assertFalse($evaluated);
        $this->assertSame('Totwell', $lazyValue->value());
        $this->assertTrue($evaluated);
    }

    public function testValueIsMemoized()
    {
        $count = 0;
        $lazyValue = new LazyValue(function () use (&$count) {
            return ++$count;
        });

        $this->assertSame(1, $lazyValue->value());
        $this->assertSame(1, $lazyValue->value());
        $this->assertSame(1, $count);
    }

    public function testNullValueIsMemoized()
    {
        $count = 0;
        $lazyValue = new LazyValue(function () use (&$count) {
            $count++;

            return null;
        });

        $this->assertNull($lazyValue->value());
        $this->assertNull($lazyValue->value());
        $this->assertSame(1, $count);
    }

    public function testValueCanBeResolvedByInvokingInstance()
    {
        $count = 0;
        $lazyValue = new LazyValue(function () use (&$count) {
            $count++;

            return 'cosmastech';
        });

        $this->assertSame('cosmastech', $lazyValue());
        $this->assertSame('cosmastech', $lazyValue());
        $this->assertSame(1, $count);
    }

    public function testCallbackIsRetriedAfterException()
    {
        $count = 0;
        $lazyValue = new LazyValue(function () use (&$count) {
            $count++;

            if ($count === 1) {
                throw new RuntimeException('Failed resolving lazy value.');
            }

            return 'Taylor';
        });

        try {
            $lazyValue->value();

            $this->fail('Exception was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Failed resolving lazy value.', $e->getMessage());
        }

        $this->assertSame('Taylor', $lazyValue->value());
        $this->assertSame(2, $count);
    }
}

class LazyValueDependency
{
    public function __construct(public string $name)
    {
        //
    }
}
