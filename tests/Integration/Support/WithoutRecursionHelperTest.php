<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Support\Recursable;
use Illuminate\Support\Recurser;
use Illuminate\Support\Str;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class WithoutRecursionHelperTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        Recurser::flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Recurser::flush();
        m::close();
    }

    public function testCallbacksAreCalledOnNonRecursiveSingleInstances()
    {
        $instance = new DoublyLinkedRecursiveList(1);

        $this->assertSame([1], $instance->downline());
        $this->assertSame(['downline' => 1, 'downline_callback' => 1], $instance->pullCallCount());

        $this->assertSame([1], $instance->upline());
        $this->assertSame(['upline' => 1, 'upline_callback' => 1], $instance->pullCallCount());

        $this->assertSame([], $instance->children());
        $this->assertSame([], $instance->pullCallCount());

        $this->assertSame([], $instance->ancestors());
        $this->assertSame([], $instance->pullCallCount());
    }

    public function testRecursionCallbacksAreNotCalledOnNonRecursiveSingleInstances()
    {
        $instance = new DoublyLinkedRecursiveList(1);

        $this->assertSame($instance, $instance->head());
        $this->assertSame(['head' => 1, 'head_callback' => 1], $instance->pullCallCount());

        $this->assertSame($instance, $instance->tail());
        $this->assertSame(['tail' => 1, 'tail_callback' => 1], $instance->pullCallCount());
    }

    public function testCallbacksAreCalledOnNonRecursiveInstances()
    {
        $instance = DoublyLinkedRecursiveList::make(children: 3);

        $this->assertSame([1, 2, 3, 4], $instance->downline());
        $this->assertSame(['downline' => 1, 'downline_callback' => 1], $instance->pullCallCount());

        $this->assertSame([1], $instance->upline());
        $this->assertSame(['upline' => 1, 'upline_callback' => 1], $instance->pullCallCount());

        $this->assertSame([2, 3, 4], $instance->children());
        $this->assertSame([], $instance->pullCallCount());

        $this->assertSame([], $instance->ancestors());
        $this->assertSame([], $instance->pullCallCount());
    }

    public function testRecursionCallbacksAreNotCalledOnNonRecursiveInstances()
    {
        $instance = DoublyLinkedRecursiveList::make(children: 3);

        $this->assertSame($instance, $instance->head());
        $this->assertSame(['head' => 1, 'head_callback' => 1], $instance->pullCallCount());

        $this->assertNotSame($instance, $instance->tail());
        $this->assertSame(['tail' => 1, 'tail_callback' => 1], $instance->pullCallCount());
    }

    public function testCallbacksAreCalledOnceOnRecursiveInstances()
    {
        $head = DoublyLinkedRecursiveList::make(children: 3);
        $tail = $head->tail();

        $head->resetCallCount();
        $tail->resetCallCount();

        // Make the list circular
        $head->setPrev($tail);

        $this->assertSame([1, 2, 3, 4], $head->downline());
        $this->assertSame(['downline' => 2, 'downline_callback' => 1], $head->pullCallCount());
        $this->assertSame(['downline' => 1, 'downline_callback' => 1], $tail->pullCallCount());

        $this->assertSame([1, 4, 3, 2], $head->upline());
        $this->assertSame(['upline' => 2, 'upline_callback' => 1], $head->pullCallCount());
        $this->assertSame(['upline' => 1, 'upline_callback' => 1], $tail->pullCallCount());

        $this->assertSame([2, 3, 4, 1], $head->children());
        $this->assertSame(['downline' => 1, 'downline_callback' => 1], $head->pullCallCount());
        $this->assertSame(['downline' => 1, 'downline_callback' => 1], $tail->pullCallCount());

        $this->assertSame([4, 3, 2, 1], $head->ancestors());
        $this->assertSame(['upline' => 1, 'upline_callback' => 1], $head->pullCallCount());
        $this->assertSame(['upline' => 2, 'upline_callback' => 1], $tail->pullCallCount());

        $this->assertSame([4, 1, 2, 3], $tail->downline());
        $this->assertSame(['downline' => 1, 'downline_callback' => 1], $head->pullCallCount());
        $this->assertSame(['downline' => 2, 'downline_callback' => 1], $tail->pullCallCount());

        $this->assertSame([4, 3, 2, 1], $tail->upline());
        $this->assertSame(['upline' => 1, 'upline_callback' => 1], $head->pullCallCount());
        $this->assertSame(['upline' => 2, 'upline_callback' => 1], $tail->pullCallCount());

        $this->assertSame([1, 2, 3, 4], $tail->children());
        $this->assertSame(['downline' => 2, 'downline_callback' => 1], $head->pullCallCount());
        $this->assertSame(['downline' => 1, 'downline_callback' => 1], $tail->pullCallCount());

        $this->assertSame([3, 2, 1, 4], $tail->ancestors());
        $this->assertSame(['upline' => 1, 'upline_callback' => 1], $head->pullCallCount());
        $this->assertSame(['upline' => 1, 'upline_callback' => 1], $tail->pullCallCount());
    }

    public function testRecursionCallbacksAreCalledOnRecursiveInstances() {
        $head = DoublyLinkedRecursiveList::make(children: 2);
        $body = $head->getNext();
        $tail = $body->getNext();

        // Make the list circular
        $head->setPrev($tail);

        $this->assertSame($body, $head->head());
        $this->assertSame(['head' => 2, 'head_callback' => 1, 'head_recursive_callback' => 1], $head->pullCallCount());
        $this->assertSame(['head' => 1, 'head_callback' => 1], $tail->pullCallCount());

        $this->assertSame($tail, $head->tail());
        $this->assertSame(['tail' => 2, 'tail_callback' => 1, 'tail_recursive_callback' => 1], $head->pullCallCount());
        $this->assertSame(['tail' => 1, 'tail_callback' => 1], $tail->pullCallCount());

        $this->assertSame($head, $tail->head());
        $this->assertSame(['head' => 1, 'head_callback' => 1], $head->pullCallCount());
        $this->assertSame(['head' => 2, 'head_callback' => 1, 'head_recursive_callback' => 1], $tail->pullCallCount());

        $this->assertSame($body, $tail->tail());
        $this->assertSame(['tail' => 1, 'tail_callback' => 1], $head->pullCallCount());
        $this->assertSame(['tail' => 2, 'tail_callback' => 1, 'tail_recursive_callback' => 1], $tail->pullCallCount());
    }

    public function testCallbacksAreCalledOnceOnSelfReferentialInstances()
    {
        $instance = tap(new DoublyLinkedRecursiveList(1), fn ($list) => $list->setNext($list));

        $this->assertSame([1], $instance->downline());
        $this->assertSame(['downline' => 2, 'downline_callback' => 1], $instance->pullCallCount());

        $this->assertSame([1], $instance->upline());
        $this->assertSame(['upline' => 2, 'upline_callback' => 1], $instance->pullCallCount());

        $this->assertSame([1], $instance->children());
        $this->assertSame(['downline' => 2, 'downline_callback' => 1], $instance->pullCallCount());

        $this->assertSame([1], $instance->ancestors());
        $this->assertSame(['upline' => 2, 'upline_callback' => 1], $instance->pullCallCount());
    }

    public function testRecursionCallbacksAreCalledOnceOnSelfReferentialInstances()
    {
        $instance = tap(new DoublyLinkedRecursiveList(1), fn ($list) => $list->setNext($list));

        $this->assertSame($instance, $instance->head());
        $this->assertSame(['head' => 2, 'head_callback' => 1, 'head_recursive_callback' => 1], $instance->pullCallCount());

        $this->assertSame($instance, $instance->tail());
        $this->assertSame(['tail' => 2, 'tail_callback' => 1, 'tail_recursive_callback' => 1], $instance->pullCallCount());
    }

    public function testWithoutRecursionWorksClosure()
    {
        $foo = function ($depth) use (&$foo) {
            return without_recursion(fn () => $foo($depth) + 1, $depth);
        };

        $this->assertSame(1, $foo(0));
        $this->assertSame(2, $foo($foo(0)));
    }

    public function testWithoutRecursionWorksInGlobalFunction()
    {
        $result = recursive_rand();
        $this->assertMatchesRegularExpression('/^\d+:\d+$/', $result);
    }

    public function testWithoutRecursionWorksInInvokableClass()
    {
        $foo = new InvokableRecursiveRepeater('foo');

        $this->assertSame('foo', $foo());
        $this->assertSame('foo:foo:foo', $foo(3));

        $bar = new InvokableRecursiveRepeater($foo);

        $this->assertSame('', $bar(0));
        $this->assertSame('foo:foo:foo:foo:foo', $bar(5));
    }

    public function testWithoutRecursionOnlyCallsRecursionCallbackOncePerCallStack()
    {
        $counter = 0;

        $onRecursion = function () use (&$counter) {
            return ++$counter;
        };

        $callback = function (int $times) use (&$callback, $onRecursion) {
            return without_recursion(function () use ($callback, $times) {
                $values = [];

                for ($i = 0; $i < $times; $i++) {
                    $values[] = $callback($times - 1);
                }

                return implode(':', $values);
            }, $onRecursion);
        };

        $this->assertSame('1:1:1', $callback(3));
        $this->assertSame(1, $counter);
        $this->assertSame('2:2:2:2:2', $callback(5));
        $this->assertSame(2, $counter);
    }

    public function testWithoutRecursionAllowsObjectOverrideWithTrace()
    {
        $recurser = MockRecurser::mock();

        $signature = sprintf('%s:%s@%s', __FILE__, __CLASS__, __FUNCTION__);
        $object = (object) [];

        $recurser->shouldReceive('withoutRecursion')
            ->once()
            ->withArgs(function (Recursable $target) use ($signature) {
                return
                    $target->signature === $signature
                    && $target->object === $this
                    && $target->hash === hash('xxh128', $signature);
            });

        without_recursion(fn () => null, null);

        $recurser->shouldReceive('withoutRecursion')
            ->once()
            ->withArgs(function (Recursable $target) use ($signature, $object) {
                return
                    $target->signature === $signature
                    && $target->object === $object
                    && $target->hash === hash('xxh128', $signature);
            });

        without_recursion(fn () => null, null, for: $object);
    }

    public function testWithoutRecursionUsesSignatureInsteadOfTrace()
    {
        $recurser = MockRecurser::mock();

        $signature = Str::random();

        $recurser->shouldReceive('withoutRecursion')
            ->once()
            ->withArgs(function (Recursable $target) use ($signature) {
                return
                    $target->signature === $signature
                    && $target->object === null
                    && $target->hash === hash('xxh128', $signature);
            });

        without_recursion(fn () => null, null, $signature);

        $signature = Str::random();
        $object = (object) [];

        $recurser->shouldReceive('withoutRecursion')
            ->once()
            ->withArgs(function (Recursable $target) use ($signature, $object) {
                return
                    $target->signature === $signature
                    && $target->object === $object
                    && $target->hash === hash('xxh128', $signature);
            });

        without_recursion(fn () => null, null, $signature, $object);
    }

    public function testRecursesSameFunctionIfSignatureDifferent()
    {
        $fibonacci = function ($number) use (&$fibonacci) {
            return without_recursion(
                fn () => $fibonacci($number - 1) + $fibonacci($number - 2),
                $number ? max(0, $number) : 1,
                as: 'fibonacci:'.($number ? max(0, $number) : 1)
            );
        };

        $this->assertSame(0, $fibonacci(0));
        $this->assertSame(1, $fibonacci(1));
        $this->assertSame(1, $fibonacci(2));
        $this->assertSame(2, $fibonacci(3));
        $this->assertSame(3, $fibonacci(4));
        $this->assertSame(5, $fibonacci(5));
        $this->assertSame(8, $fibonacci(6));
        $this->assertSame(13, $fibonacci(7));
        $this->assertSame(21, $fibonacci(8));
        $this->assertSame(34, $fibonacci(9));
        $this->assertSame(55, $fibonacci(10));
        $this->assertSame(0, $fibonacci(-10));
    }
}

class MockRecurser extends Recurser
{
    public static function mock()
    {
        return Recurser::$instance = m::mock(static::class);
    }
}

function recursive_rand(): string
{
    return without_recursion(
        fn () => sprintf('%d:%s', rand(1, 10000), recursive_rand()),
        rand(1, 10000)
    );
}

class InvokableRecursiveRepeater
{
    public function __construct(
        protected mixed $value,
    ) {
        //
    }

    public function __invoke(int $times = 1): string
    {
        return without_recursion(
            function () use ($times) {
                $values = [];

                for ($i = 0; $i < $times; $i++) {
                    $values[] = (string) $this();
                }

                return implode(':', $values);
            },
            $this->value,
        );
    }
}

class DoublyLinkedRecursiveList
{
    protected array $callCount = [];

    public function __construct(
        public readonly int $id,
        protected ?self $next = null,
        protected ?self $prev = null,
    ) {
        $this->next?->setPrev($this);
        $this->prev?->setNext($this);
    }

    public static function make(int $id = 1, int $children = 0): self
    {
        return new self($id, $children > 0 ? self::make($id + 1, $children - 1) : null);
    }

    public function getNext(): ?self
    {
        return $this->next;
    }

    public function getPrev(): ?self
    {
        return $this->prev;
    }

    public function setNext(self $next): void
    {
        if ($this->next !== $next) {
            $this->next = $next;

            $next->setPrev($this);
        }
    }

    public function setPrev(self $prev): void
    {
        if ($this->prev !== $prev) {
            $this->prev = $prev;

            $prev->setNext($this);
        }
    }

    public function ancestors(): array
    {
        return $this->prev?->upline() ?? [];
    }

    public function children(): array
    {
        return $this->next?->downline() ?? [];
    }

    public function upline(): array
    {
        $this->recordCall('upline');

        return without_recursion(function () {
            $this->recordCall('upline_callback');

            return [
                $this->id,
                ...($this->getPrev()?->upline() ?? []),
            ];
        }, []);
    }

    public function downline(): array
    {
        $this->recordCall('downline');

        return without_recursion(function () {
            $this->recordCall('downline_callback');

            return [
                $this->id,
                ...($this->getNext()?->downline() ?? []),
            ];
        }, []);
    }

    public function head(): self
    {
        $this->recordCall('head');

        return without_recursion(function () {
            $this->recordCall('head_callback');

            return $this->getPrev()?->head() ?? $this;
        }, function () {
            $this->recordCall('head_recursive_callback');

            return $this->getNext();
        });
    }

    public function tail(): self
    {
        $this->recordCall('tail');

        return without_recursion(function () {
            $this->recordCall('tail_callback');

            return $this->getNext()?->tail() ?? $this;
        }, function () {
            $this->recordCall('tail_recursive_callback');

            return $this->getPrev();
        });
    }

    public function pullCallCount(): array
    {
        return tap($this->callCount, fn () => $this->resetCallCount());
    }

    public function resetCallCount(): void
    {
        $this->callCount = [];
    }

    protected function recordCall(string $function): void
    {
        $this->callCount[$function] ??= 0;

        $this->callCount[$function]++;
    }
}
