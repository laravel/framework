<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\BufferedStore;
use Illuminate\Cache\BufferedTaggedCache;
use Illuminate\Cache\Repository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CacheBufferedStoreTest extends TestCase
{
    public function testLeastRecentlyUsedValueIsEvictedWhenFull(): void
    {
        $fallback = $this->fallback(['a' => 1, 'b' => 2, 'c' => 3]);
        $store = new BufferedStore('array', $fallback, 2);

        $store->get('a');
        $store->get('b');
        $store->get('c');

        $fallback->putMany(['a' => 10, 'b' => 20, 'c' => 30], 60);

        $this->assertSame(3, $store->get('c'));
        $this->assertSame(10, $store->get('a'));
    }

    public function testHitMovesValueToTheTop(): void
    {
        $fallback = $this->fallback(['a' => 1, 'b' => 2, 'c' => 3]);
        $store = new BufferedStore('array', $fallback, 2);

        $store->get('a');
        $store->get('b');
        $store->get('a');
        $store->get('c');

        $fallback->putMany(['a' => 10, 'b' => 20], 60);

        $this->assertSame(1, $store->get('a'));
        $this->assertSame(20, $store->get('b'));
    }

    public function testManyBuffersAndEvicts(): void
    {
        $fallback = $this->fallback(['a' => 1, 'b' => 2, 'c' => 3]);
        $store = new BufferedStore('array', $fallback, 2);

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3, 'd' => null], $store->many(['a', 'b', 'c', 'd']));

        $fallback->putMany(['a' => 10, 'b' => 20, 'c' => 30, 'd' => 40], 60);

        $this->assertSame(['a' => 10, 'b' => 20, 'c' => 3, 'd' => null], $store->many(['a', 'b', 'c', 'd']));
    }

    public function testWritesForgetBufferedValue(): void
    {
        $fallback = $this->fallback(['a' => 1]);
        $store = new BufferedStore('array', $fallback, 2);

        $store->get('a');
        $store->put('a', 2, 60);

        $this->assertSame(2, $store->get('a'));
    }

    public function testTaggedValuesShareTheBufferSize(): void
    {
        $fallback = $this->fallback(['a' => 1]);
        $fallback->tags('tag')->put('b', 2, 60);

        $store = new BufferedStore('array', $fallback, 1);

        $this->assertInstanceOf(BufferedTaggedCache::class, $store->tags('tag'));

        $store->get('a');
        $this->assertSame(2, $store->tags('tag')->get('b'));

        $fallback->put('a', 10, 60);
        $fallback->tags('tag')->put('b', 20, 60);

        $this->assertSame(['b' => 2], $store->tags('tag')->many(['b']));
        $this->assertSame(10, $store->get('a'));
        $this->assertSame(20, $store->tags('tag')->get('b'));
    }

    public function testFlushingTagsClearsTheBuffer(): void
    {
        $fallback = $this->fallback(['a' => 1]);
        $fallback->tags('tag')->put('b', 2, 60);

        $store = new BufferedStore('array', $fallback, 10);
        $store->get('a');
        $store->tags('tag')->get('b');

        $store->tags('tag')->flush();
        $fallback->put('a', 10, 60);

        $this->assertNull($store->tags('tag')->get('b'));
        $this->assertSame(10, $store->get('a'));
    }

    public function testSizeMustBeAtLeastOne(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BufferedStore('array', $this->fallback(), 'abc');
    }

    public function testRepositoryCanBeBuffered(): void
    {
        $repository = $this->fallback(['foo' => 'bar']);

        $buffered = $repository->buffered(10);

        $this->assertInstanceOf(BufferedStore::class, $buffered->getStore());
        $this->assertSame('array', $buffered->getName());
        $this->assertSame('bar', $buffered->get('foo'));

        $repository->put('foo', 'baz', 60);

        $this->assertSame('bar', $buffered->get('foo'));
    }

    public function testTaggedRepositoryCanBeBuffered(): void
    {
        $repository = $this->fallback();
        $repository->tags('tag')->put('foo', 'bar', 60);

        $this->assertSame('bar', $repository->tags('tag')->buffered()->get('foo'));
    }

    protected function fallback(array $values = []): Repository
    {
        $repository = new Repository(new ArrayStore, ['store' => 'array']);

        if ($values !== []) {
            $repository->putMany($values, 60);
        }

        return $repository;
    }
}
