<?php

namespace Illuminate\Tests\Support;

use Carbon\CarbonInterval;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Once;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class OnceTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Once::flush();
        Once::enable();
    }

    public function testResultIsMemoized()
    {
        $instance = new class {
            public function rand()
            {
                return once(fn () => rand(1, PHP_INT_MAX));
            }
        };

        $first = $instance->rand();
        $second = $instance->rand();

        $this->assertSame($first, $second);
    }

    public function testCallableIsOnlyCalledOnce()
    {
        $instance = new class {
            public int $count = 0;

            public function increment()
            {
                return once(fn () => ++$this->count);
            }
        };

        $first = $instance->increment();
        $second = $instance->increment();

        $this->assertSame(1, $first);
        $this->assertSame(1, $second);
        $this->assertSame(1, $instance->count);
    }

    public function testResultIsNotMemoizedWhenFlushed()
    {
        $instance = new MyClass();

        $first = $instance->rand();
        Once::flush();
        $second = $instance->rand();

        $this->assertNotSame($first, $second);
    }

    public function testResultIsNotMemoizedWhenObjectIsGarbageCollected()
    {
        $instance = new MyClass();

        $first = $instance->rand();
        unset($instance);
        gc_collect_cycles();
        $instance = new MyClass();
        $second = $instance->rand();

        $this->assertNotSame($first, $second);
    }

    public function testResultIsNotMemoizedWhenUsesChange()
    {
        $instance = new class() {
            public function rand(string $letter)
            {
                return once(function () use ($letter) {
                    return $letter.rand(1, 10000000);
                });
            }
        };

        $first = $instance->rand('a');
        $second = $instance->rand('b');

        $this->assertNotSame($first, $second);

        $first = $instance->rand('a');
        $second = $instance->rand('a');

        $this->assertSame($first, $second);
    }

    public function testResultIsMemoizedWhenCalledStatically()
    {
        $first = MyClass::staticRand();
        $second = MyClass::staticRand();

        $this->assertSame($first, $second);
    }

    public function testResultIsMemoizedWhenCalledWithinAClosure()
    {
        $resolver = fn () => once(fn () => rand(1, PHP_INT_MAX));

        $first = $resolver();
        $second = $resolver();

        $this->assertSame($first, $second);
    }

    public function testResultIsMemoizedWhenCalledGlobally()
    {
        $first = my_rand();
        $second = my_rand();

        $this->assertSame($first, $second);
    }

    public function testResultIsNotMemoizedWhenOnceIsDisabled()
    {
        Once::disable();

        $first = my_rand();
        $second = my_rand();

        $this->assertNotSame($first, $second);
    }

    public function testResultMayBeMemoizedTemporarily()
    {
        $first = my_rand();
        $second = my_rand();

        Once::disable();

        $third = my_rand();

        Once::enable();

        $fourth = my_rand();

        $this->assertSame($first, $second);
        $this->assertNotSame($first, $third);
        $this->assertSame($first, $fourth);
    }

    public function testResultMayBeMemoizedWithinEval()
    {
        $resolver = eval('return fn () => once( function () { return random_int(1, 1000); } ) ;');

        $first = $resolver();
        $second = $resolver();

        $this->assertSame($first, $second);
    }

    public function testResultIsDifferentWhenCalledFromDifferentClosures()
    {
        $resolver = fn () => once(fn () => rand(1, PHP_INT_MAX));
        $resolver2 = fn () => once(fn () => rand(1, PHP_INT_MAX));

        $first = $resolver();
        $second = $resolver2();

        $this->assertNotSame($first, $second);
    }

    public function testResultIsMemoizedWhenCalledFromMethodsWithSameName()
    {
        $instanceA = new class {
            public function rand()
            {
                return once(fn () => rand(1, PHP_INT_MAX));
            }
        };

        $instanceB = new class {
            public function rand()
            {
                return once(fn () => rand(1, PHP_INT_MAX));
            }
        };

        $first = $instanceA->rand();
        $second = $instanceB->rand();

        $this->assertNotSame($first, $second);
    }

    public function testRecursiveOnceCalls()
    {
        $instance = new class {
            public function rand()
            {
                return once(fn() => once(fn() => rand(1, PHP_INT_MAX)));
            }
        };

        $first = $instance->rand();
        $second = $instance->rand();

        $this->assertSame($first, $second);
    }
}

function my_rand()
{
    return once(fn () => rand(1, PHP_INT_MAX));
}

class MyClass
{
    public function rand()
    {
        return once(fn () => rand(1, PHP_INT_MAX));
    }

    public static function staticRand()
    {
        return once(fn () => rand(1, PHP_INT_MAX));
    }
}
