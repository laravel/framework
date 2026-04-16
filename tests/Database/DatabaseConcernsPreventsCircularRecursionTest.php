<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Concerns\PreventsCircularRecursion;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseConcernsPreventsCircularRecursionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        PreventsCircularRecursionWithRecursiveMethod::$globalStack = 0;
    }

    public function testRecursiveCallsArePreventedWithoutPreventingSubsequentCalls()
    {
        $instance = new PreventsCircularRecursionWithRecursiveMethod();

        $this->assertSame(0, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(0, $instance->instanceStack);

        $this->assertSame(0, $instance->callStack());
        $this->assertSame(1, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(1, $instance->instanceStack);

        $this->assertSame(1, $instance->callStack());
        $this->assertSame(2, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(2, $instance->instanceStack);
    }

    public function testRecursiveDefaultCallbackIsCalledOnlyOnRecursion()
    {
        $instance = new PreventsCircularRecursionWithRecursiveMethod();

        $this->assertSame(0, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(0, $instance->instanceStack);
        $this->assertSame(0, $instance->defaultStack);

        $this->assertEquals(['instance' => 1, 'default' => 0], $instance->callCallableDefaultStack());
        $this->assertSame(1, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(1, $instance->instanceStack);
        $this->assertSame(1, $instance->defaultStack);

        $this->assertEquals(['instance' => 2, 'default' => 1], $instance->callCallableDefaultStack());
        $this->assertSame(2, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(2, $instance->instanceStack);
        $this->assertSame(2, $instance->defaultStack);
    }

    public function testRecursiveDefaultCallbackIsCalledOnlyOncePerCallStack()
    {
        $instance = new PreventsCircularRecursionWithRecursiveMethod();

        $this->assertSame(0, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(0, $instance->instanceStack);
        $this->assertSame(0, $instance->defaultStack);

        $this->assertEquals(
            [
                ['instance' => 1, 'default' => 0],
                ['instance' => 1, 'default' => 0],
                ['instance' => 1, 'default' => 0],
            ],
            $instance->callCallableDefaultStackRepeatedly(),
        );
        $this->assertSame(1, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(1, $instance->instanceStack);
        $this->assertSame(1, $instance->defaultStack);

        $this->assertEquals(
            [
                ['instance' => 2, 'default' => 1],
                ['instance' => 2, 'default' => 1],
                ['instance' => 2, 'default' => 1],
            ],
            $instance->callCallableDefaultStackRepeatedly(),
        );
        $this->assertSame(2, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(2, $instance->instanceStack);
        $this->assertSame(2, $instance->defaultStack);
    }

    public function testRecursiveCallsAreLimitedToIndividualInstances()
    {
        $instance = new PreventsCircularRecursionWithRecursiveMethod();
        $other = $instance->other;

        $this->assertSame(0, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(0, $instance->instanceStack);
        $this->assertSame(0, $other->instanceStack);

        $instance->callStack();
        $this->assertSame(1, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(1, $instance->instanceStack);
        $this->assertSame(0, $other->instanceStack);

        $instance->callStack();
        $this->assertSame(2, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(2, $instance->instanceStack);
        $this->assertSame(0, $other->instanceStack);

        $other->callStack();
        $this->assertSame(3, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(2, $instance->instanceStack);
        $this->assertSame(1, $other->instanceStack);

        $other->callStack();
        $this->assertSame(4, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(2, $instance->instanceStack);
        $this->assertSame(2, $other->instanceStack);
    }

    public function testRecursiveCallsToCircularReferenceCallsOtherInstanceOnce()
    {
        $instance = new PreventsCircularRecursionWithRecursiveMethod();
        $other = $instance->other;

        $this->assertSame(0, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(0, $instance->instanceStack);
        $this->assertSame(0, $other->instanceStack);

        $instance->callOtherStack();
        $this->assertSame(2, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(1, $instance->instanceStack);
        $this->assertSame(1, $other->instanceStack);

        $instance->callOtherStack();
        $this->assertSame(4, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(2, $instance->instanceStack);
        $this->assertSame(2, $other->instanceStack);

        $other->callOtherStack();
        $this->assertSame(6, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(3, $other->instanceStack);
        $this->assertSame(3, $instance->instanceStack);

        $other->callOtherStack();
        $this->assertSame(8, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(4, $other->instanceStack);
        $this->assertSame(4, $instance->instanceStack);
    }

    public function testRecursiveCallsToCircularLinkedListCallsEachInstanceOnce()
    {
        $instance = new PreventsCircularRecursionWithRecursiveMethod();
        $second = $instance->other;
        $third = new PreventsCircularRecursionWithRecursiveMethod($second);
        $instance->other = $third;

        $this->assertSame(0, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(0, $instance->instanceStack);
        $this->assertSame(0, $second->instanceStack);
        $this->assertSame(0, $third->instanceStack);

        $instance->callOtherStack();
        $this->assertSame(3, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(1, $instance->instanceStack);
        $this->assertSame(1, $second->instanceStack);
        $this->assertSame(1, $third->instanceStack);

        $second->callOtherStack();
        $this->assertSame(6, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(2, $instance->instanceStack);
        $this->assertSame(2, $second->instanceStack);
        $this->assertSame(2, $third->instanceStack);

        $third->callOtherStack();
        $this->assertSame(9, PreventsCircularRecursionWithRecursiveMethod::$globalStack);
        $this->assertSame(3, $instance->instanceStack);
        $this->assertSame(3, $second->instanceStack);
        $this->assertSame(3, $third->instanceStack);
    }

    public function testMockedModelCallToWithoutRecursionMethodWorks(): void
    {
        $mock = m::mock(TestModel::class)->makePartial();

        // Model toArray method implementation
        $toArray = $mock->withoutRecursion(
            fn () => array_merge($mock->attributesToArray(), $mock->relationsToArray()),
            fn () => $mock->attributesToArray(),
        );
        $this->assertEquals([], $toArray);
    }
}

class PreventsCircularRecursionWithRecursiveMethod
{
    use PreventsCircularRecursion;

    public function __construct(
        public ?PreventsCircularRecursionWithRecursiveMethod $other = null,
    ) {
        $this->other ??= new PreventsCircularRecursionWithRecursiveMethod($this);
    }

    public static int $globalStack = 0;
    public int $instanceStack = 0;
    public int $defaultStack = 0;

    public function callStack(): int
    {
        return $this->withoutRecursion(
            function () {
                static::$globalStack++;
                $this->instanceStack++;

                return $this->callStack();
            },
            $this->instanceStack,
        );
    }

    public function callCallableDefaultStack(): array
    {
        return $this->withoutRecursion(
            function () {
                static::$globalStack++;
                $this->instanceStack++;

                return $this->callCallableDefaultStack();
            },
            fn () => [
                'instance' => $this->instanceStack,
                'default' => $this->defaultStack++,
            ],
        );
    }

    public function callCallableDefaultStackRepeatedly(): array
    {
        return $this->withoutRecursion(
            function () {
                static::$globalStack++;
                $this->instanceStack++;

                return [
                    $this->callCallableDefaultStackRepeatedly(),
                    $this->callCallableDefaultStackRepeatedly(),
                    $this->callCallableDefaultStackRepeatedly(),
                ];
            },
            fn () => [
                'instance' => $this->instanceStack,
                'default' => $this->defaultStack++,
            ],
        );
    }

    public function callOtherStack(): int
    {
        return $this->withoutRecursion(
            function () {
                $this->other->callStack();

                return $this->other->callOtherStack();
            },
            $this->instanceStack,
        );
    }
}
