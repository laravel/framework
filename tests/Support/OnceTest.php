<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Once;
use PHPUnit\Framework\TestCase;

class OnceTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Once::flush();
        Once::enable();
    }

    public function testResultMemoization()
    {
        $instance = new class
        {
            public function rand()
            {
                return once(fn () => rand(1, PHP_INT_MAX));
            }
        };

        $first = $instance->rand();
        $second = $instance->rand();

        $this->assertSame($first, $second);
    }

    public function testCallableIsCalledOnce()
    {
        $instance = new class
        {
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

    public function testFlush()
    {
        $instance = new MyClass();

        $first = $instance->rand();

        Once::flush();

        $second = $instance->rand();

        $this->assertNotSame($first, $second);

        Once::disable();
        Once::flush();

        $first = $instance->rand();
        $second = $instance->rand();

        $this->assertNotSame($first, $second);
    }

    public function testNotMemoizedWhenObjectIsGarbageCollected()
    {
        $instance = new MyClass();

        $first = $instance->rand();
        unset($instance);
        gc_collect_cycles();
        $instance = new MyClass();
        $second = $instance->rand();

        $this->assertNotSame($first, $second);
    }

    public function testIsNotMemoizedWhenCallableUsesChanges()
    {
        $instance = new class()
        {
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

        $results = [];
        $letter = 'a';

        a:
        $results[] = once(fn () => $letter.rand(1, 10000000));

        if (count($results) < 2) {
            goto a;
        }

        $this->assertSame($results[0], $results[1]);
    }

    public function testUsageOfThis()
    {
        $instance = new MyClass();

        $first = $instance->callRand();
        $second = $instance->callRand();

        $this->assertSame($first, $second);
    }

    public function testInvokables()
    {
        $invokable = new class
        {
            public static $count = 0;

            public function __invoke()
            {
                return static::$count = static::$count + 1;
            }
        };

        $instance = new class($invokable)
        {
            public function __construct(protected $invokable)
            {
            }

            public function call()
            {
                return once($this->invokable);
            }
        };

        $first = $instance->call();
        $second = $instance->call();
        $third = $instance->call();

        $this->assertSame($first, $second);
        $this->assertSame($first, $third);
        $this->assertSame(1, $invokable::$count);
    }

    public function testFirstClassCallableSyntax()
    {
        $instance = new class
        {
            public function rand()
            {
                return once(MyClass::staticRand(...));
            }
        };

        $first = $instance->rand();
        $second = $instance->rand();

        $this->assertSame($first, $second);
    }

    public function testFirstClassCallableSyntaxWithArraySyntax()
    {
        $instance = new class
        {
            public function rand()
            {
                return once([MyClass::class, 'staticRand']);
            }
        };

        $first = $instance->rand();
        $second = $instance->rand();

        $this->assertSame($first, $second);
    }

    public function testStaticMemoization()
    {
        $first = MyClass::staticRand();
        $second = MyClass::staticRand();

        $this->assertSame($first, $second);
    }

    public function testMemoizationWhenOnceIsWithinClosure()
    {
        $resolver = fn () => once(fn () => rand(1, PHP_INT_MAX));

        $first = $resolver();
        $second = $resolver();

        $this->assertSame($first, $second);
    }

    public function testMemoizationOnGlobalFunctions()
    {
        $first = my_rand();
        $second = my_rand();

        $this->assertSame($first, $second);
    }

    public function testDisable()
    {
        Once::disable();

        $first = my_rand();
        $second = my_rand();

        $this->assertNotSame($first, $second);
    }

    public function testTemporaryDisable()
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

    public function testMemoizationWithinEvals()
    {
        $firstResolver = eval('return fn () => once( function () { return random_int(1, PHP_INT_MAX); } ) ;');

        $firstA = $firstResolver();
        $firstB = $firstResolver();

        $secondResolver = eval('return fn () => fn () => once( function () { return random_int(1, PHP_INT_MAX); } ) ;');

        $secondA = $secondResolver()();
        $secondB = $secondResolver()();

        $third = eval('return once( function () { return random_int(1, PHP_INT_MAX); } ) ;');
        $fourth = eval('return once( function () { return random_int(1, PHP_INT_MAX); } ) ;');

        $this->assertNotSame($firstA, $firstB);
        $this->assertNotSame($secondA, $secondB);
        $this->assertNotSame($third, $fourth);
    }

    public function testMemoizationOnSameLine()
    {
        $this->markTestSkipped('This test shows a limitation of the current implementation.');

        $result = [once(fn () => rand(1, PHP_INT_MAX)), once(fn () => rand(1, PHP_INT_MAX))];

        $this->assertNotSame($result[0], $result[1]);
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
        $instanceA = new class
        {
            public function rand()
            {
                return once(fn () => rand(1, PHP_INT_MAX));
            }
        };

        $instanceB = new class
        {
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
        $instance = new class
        {
            public function rand()
            {
                return once(fn () => once(fn () => rand(1, PHP_INT_MAX)));
            }
        };

        $first = $instance->rand();
        $second = $instance->rand();

        $this->assertSame($first, $second);
    }

    public function testGlobalClosures()
    {
        $first = $GLOBALS['onceable1']();
        $second = $GLOBALS['onceable1']();

        $this->assertSame($first, $second);

        $third = $GLOBALS['onceable2']();
        $fourth = $GLOBALS['onceable2']();

        $this->assertSame($third, $fourth);

        $this->assertNotSame($first, $third);
    }
}

$letter = 'a';

$GLOBALS['onceable1'] = fn () => once(fn () => $letter.rand(1, PHP_INT_MAX));
$GLOBALS['onceable2'] = fn () => once(fn () => $letter.rand(1, PHP_INT_MAX));

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

    public function callRand()
    {
        return once(fn () => $this->rand());
    }
}
