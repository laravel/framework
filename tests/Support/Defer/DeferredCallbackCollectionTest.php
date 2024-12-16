<?php

declare(strict_types=1);

namespace Illuminate\Tests\Support\Defer;

use Illuminate\Support\Defer\DeferredCallback;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use PHPUnit\Framework\TestCase;

class DeferredCallbackCollectionTest extends TestCase
{
    public function testFirstMethod(): void
    {
        $collection = new DeferredCallbackCollection();
        $callback = new DeferredCallback(fn () => 'test');
        $collection[] = $callback;

        $this->assertSame($callback, $collection->first());
    }

    public function testForgetMethod(): void
    {
        $collection = new DeferredCallbackCollection();
        $callback = new DeferredCallback(fn () => 'test', 'test');
        $collection[] = $callback;

        $collection->forget('test');

        $this->assertCount(0, $collection);
    }

    public function testFilterMethod(): void
    {
        $collection = new DeferredCallbackCollection();
        $callback1 = new DeferredCallback(fn () => 'test1', 'callback1');
        $callback2 = new DeferredCallback(fn () => 'test2', 'callback2');
        $collection[] = $callback1;
        $collection[] = $callback2;

        $filteredCollection = $collection->filter(fn ($callback) => $callback->name === 'callback1');

        $this->assertCount(1, $filteredCollection);
        $this->assertSame($callback1, $filteredCollection->first());
    }

    public function testRejectMethod(): void
    {
        $collection = new DeferredCallbackCollection();
        $callback1 = new DeferredCallback(fn () => 'test1', 'callback1');
        $callback2 = new DeferredCallback(fn () => 'test2', 'callback2');
        $collection[] = $callback1;
        $collection[] = $callback2;

        $rejectedCollection = $collection->reject(fn ($callback) => $callback->name === 'callback1');

        $this->assertCount(1, $rejectedCollection);
        $this->assertSame($callback2, $rejectedCollection->first());
    }

    public function testOffsetExistsMethod(): void
    {
        $collection = new DeferredCallbackCollection();
        $callback = new DeferredCallback(fn () => 'test');
        $collection[] = $callback;

        $this->assertTrue(isset($collection[0]));
    }

    public function testOffsetGetMethod(): void
    {
        $collection = new DeferredCallbackCollection();
        $callback = new DeferredCallback(fn () => 'test');
        $collection[] = $callback;

        $this->assertSame($callback, $collection[0]);
    }

    public function testOffsetSetMethod(): void
    {
        $collection = new DeferredCallbackCollection();
        $callback = new DeferredCallback(fn () => 'test');
        $collection[0] = $callback;

        $this->assertSame($callback, $collection[0]);
    }

    public function testOffsetUnsetMethod(): void
    {
        $collection = new DeferredCallbackCollection();
        $callback = new DeferredCallback(fn () => 'test');
        $collection[] = $callback;

        unset($collection[0]);

        $this->assertFalse(isset($collection[0]));
    }

    public function testCountMethod(): void
    {
        $collection = new DeferredCallbackCollection();
        $collection[] = new DeferredCallback(fn () => 'test');

        $this->assertCount(1, $collection);
    }
}
