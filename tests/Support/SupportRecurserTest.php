<?php

namespace Illuminate\Tests\Support;

use BadMethodCallException;
use Illuminate\Support\Exceptions\RecursableNotFoundException;
use Illuminate\Support\Recursable;
use Illuminate\Support\Recurser;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use WeakMap;

class SupportRecurserTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        Recurser::flush();
        RecurserStub::flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Recurser::flush();
        RecurserStub::flush();
    }

    public function testInstanceGeneratesNewInstanceIfEmpty()
    {
        $this->assertNull(RecurserStub::getInstance());
        $instance = RecurserStub::instance();

        $this->assertInstanceOf(RecurserStub::class, $instance);
        $this->assertSame($instance, RecurserStub::getInstance());
        $this->assertSame($instance, RecurserStub::instance());

        RecurserStub::flush();

        $this->assertInstanceOf(RecurserStub::class, RecurserStub::instance());
        $this->assertNotSame($instance, RecurserStub::getInstance());
        $this->assertNotSame($instance, RecurserStub::instance());
        $this->assertSame(RecurserStub::getInstance(), RecurserStub::instance());
    }

    public function testRecurserStackManagement()
    {
        $one = (object) [];
        $two = (object) [];

        $recurser = RecurserStub::instance();

        $this->assertSame(0, $recurser->getCache()->count());
        $this->assertFalse($recurser->getCache()->offsetExists($one));
        $this->assertFalse($recurser->getCache()->offsetExists($two));
        $this->assertSame([], $recurser->expose_getStack($one));
        $this->assertSame([], $recurser->expose_getStack($two));

        $recurser->expose_setStack($one, ['foo' => 'bar']);
        $this->assertSame(1, $recurser->getCache()->count());
        $this->assertTrue($recurser->getCache()->offsetExists($one));
        $this->assertFalse($recurser->getCache()->offsetExists($two));
        $this->assertSame(['foo' => 'bar'], $recurser->expose_getStack($one));
        $this->assertSame([], $recurser->expose_getStack($two));

        $recurser->expose_setStack($two, ['foo' => 'bar', 'bing' => 'bang']);
        $this->assertSame(2, $recurser->getCache()->count());
        $this->assertTrue($recurser->getCache()->offsetExists($one));
        $this->assertTrue($recurser->getCache()->offsetExists($two));
        $this->assertSame(['foo' => 'bar'], $recurser->expose_getStack($one));
        $this->assertSame(['foo' => 'bar', 'bing' => 'bang'], $recurser->expose_getStack($two));

        $recurser->expose_setStack($one, []);
        $this->assertSame(1, $recurser->getCache()->count());
        $this->assertFalse($recurser->getCache()->offsetExists($one));
        $this->assertTrue($recurser->getCache()->offsetExists($two));
        $this->assertSame([], $recurser->expose_getStack($one));
        $this->assertSame(['foo' => 'bar', 'bing' => 'bang'], $recurser->expose_getStack($two));

        $recurser->expose_setStack($two, []);
        $this->assertSame(0, $recurser->getCache()->count());
        $this->assertFalse($recurser->getCache()->offsetExists($one));
        $this->assertFalse($recurser->getCache()->offsetExists($two));
        $this->assertSame([], $recurser->expose_getStack($one));
        $this->assertSame([], $recurser->expose_getStack($two));
    }

    public function testRecurserRecursableManagement()
    {
        $recurser = RecurserStub::instance();
        $cache = $recurser->getCache();

        $one = (object) [];
        $two = (object) [];

        $foo = RecursableMock::make('foo', 'oof', $one, 'one@foo', 'foo_hash');
        $bar = RecursableMock::make('bar', 'baz', $one, 'one@bar', 'bar_hash');
        $bing = RecursableMock::make('bing', 'bang', $two, 'two@bing', 'bing_hash');

        $this->assertSame(0, $cache->count());
        $this->assertFalse($recurser->expose_hasValue($foo));
        $this->assertFalse($recurser->expose_hasValue($bar));
        $this->assertFalse($recurser->expose_hasValue($bing));

        $recurser->expose_setRecursedValue($foo);
        $this->assertSame(1, $cache->count());
        $this->assertTrue($cache->offsetExists($one));
        $this->assertSame(['foo_hash' => 'oof'], $cache->offsetGet($one));
        $this->assertFalse($cache->offsetExists($two));

        $this->assertTrue($recurser->expose_hasValue($foo));
        $this->assertFalse($recurser->expose_hasValue($bar));
        $this->assertFalse($recurser->expose_hasValue($bing));
        $this->assertSame('oof', $recurser->expose_getRecursedValue($foo));

        $recurser->expose_setRecursedValue($bing);
        $this->assertSame(2, $cache->count());
        $this->assertTrue($cache->offsetExists($one));
        $this->assertTrue($cache->offsetExists($two));
        $this->assertSame(['bing_hash' => 'bang'], $cache->offsetGet($two));

        $this->assertTrue($recurser->expose_hasValue($foo));
        $this->assertFalse($recurser->expose_hasValue($bar));
        $this->assertTrue($recurser->expose_hasValue($bing));
        $this->assertSame('bang', $recurser->expose_getRecursedValue($bing));

        $recurser->expose_setRecursedValue($bar);
        $this->assertSame(2, $cache->count());
        $this->assertSame(['foo_hash' => 'oof', 'bar_hash' => 'baz'], $cache->offsetGet($one));

        $this->assertTrue($recurser->expose_hasValue($foo));
        $this->assertTrue($recurser->expose_hasValue($bar));
        $this->assertTrue($recurser->expose_hasValue($bing));
        $this->assertSame('baz', $recurser->expose_getRecursedValue($bar));

        $recurser->expose_release($foo);
        $this->assertSame(2, $cache->count());
        $this->assertTrue($cache->offsetExists($one));
        $this->assertTrue($cache->offsetExists($two));
        $this->assertSame(['bar_hash' => 'baz'], $cache->offsetGet($one));

        $recurser->expose_release($bar);
        $this->assertSame(1, $cache->count());
        $this->assertFalse($cache->offsetExists($one));
        $this->assertTrue($cache->offsetExists($two));

        $this->assertFalse($recurser->expose_hasValue($foo));
        $this->assertFalse($recurser->expose_hasValue($bar));
        $this->assertTrue($recurser->expose_hasValue($bing));

        $this->expectException(RecursableNotFoundException::class);
        $this->expectExceptionMessage('[one@foo]');
        $recurser->expose_getRecursedValue($foo);

        $this->expectExceptionMessage('[one@bar]');
        $recurser->expose_getRecursedValue($bar);
    }

    public function testGetRecursedValueResolvesCallablesWhenCalled()
    {
        $recurser = RecurserStub::instance();
        $cache = $recurser->getCache();

        $callable = fn () => 'foo';

        $target = new Recursable(
            fn () => 'bar',
            $callable,
            $this,
            'test',
        );

        $this->assertFalse($recurser->expose_hasValue($target));
        $recurser->expose_setRecursedValue($target);
        $this->assertTrue($recurser->expose_hasValue($target));
        $this->assertSame([$target->hash => $callable], $cache->offsetGet($this));
        $this->assertSame('foo', $recurser->expose_getRecursedValue($target));
        $this->assertSame([$target->hash => 'foo'], $cache->offsetGet($this));

        $cache->offsetSet($this, [$target->hash => fn () => 'bing']);
        $this->assertSame('bing', $recurser->expose_getRecursedValue($target));
        $this->assertSame([$target->hash => 'bing'], $cache->offsetGet($this));
    }

    public function testWithoutRecursionCallsSetsGlobalObject()
    {
        $recurser = RecurserStub::instance();
        $target = RecursableMock::make();

        $this->assertNull($target->object);
        $recurser->withoutRecursion($target);
        $this->assertSame($recurser->globalContext, $target->object);
    }

    public function testWithoutRecursionCallsRecursableCallback()
    {
        $called = false;

        $target = RecursableMock::make(function () use (&$called) {
            return $called = true;
        });

        $this->assertFalse($called);
        $this->assertTrue(RecurserStub::instance()->withoutRecursion($target));
        $this->assertTrue($called);
    }

    public function testWithoutRecursionAddsAndRemovesItemFromCallStackForObject()
    {
        $recurser = RecurserStub::instance();
        $cache = $recurser->getCache();

        $object = new class($this)
        {
            public function __construct(
                protected TestCase $test,
            ) {
                //
            }

            public function __invoke(): void
            {
                $recurser = RecurserStub::instance();
                $cache = $recurser->getCache();

                $this->test->assertSame(1, $cache->count());
                $this->test->assertTrue($cache->offsetExists($this));
                $this->test->assertSame(['foo' => null], $recurser->expose_getStack($this));
            }
        };

        $target = new Recursable(
            $object,
            null,
            $object,
            '$object@__invoke',
            'foo',
        );

        $this->assertSame(0, $cache->count());
        $this->assertFalse($recurser->expose_hasValue($target));
        $this->assertSame([], $recurser->expose_getStack($object));

        $recurser->withoutRecursion($target);

        $this->assertSame(0, $cache->count());
        $this->assertFalse($recurser->expose_hasValue($target));
        $this->assertSame([], $recurser->expose_getStack($object));
    }
}

class RecurserStub extends Recurser
{
    public static function getInstance(): ?self
    {
        return static::$instance;
    }

    public function getCache(): WeakMap
    {
        return $this->cache;
    }

    public function __call(string $method, array $parameters)
    {
        $method = str_starts_with($method, 'expose_') ? Str::after($method, 'expose_') : $method;

        return method_exists($this, $method) ? $this->$method(...$parameters) : throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}

class RecursableMock extends Recursable
{
    public static function make(
        mixed $first = null,
        mixed $second = null,
        ?object $for = null,
        string $signature = 'test',
        ?string $hash = null,
    ): static {
        return new static(
            is_callable($first) ? $first : fn () => $first,
            $second,
            $for,
            $signature,
            $hash,
        );
    }
}
