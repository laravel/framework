<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Cache\StorageStore;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Tests\Cache\Fixtures\ArrayFilesystem;
use Illuminate\Tests\Console\Fixtures\FakeCacheFactory;
use PHPUnit\Framework\TestCase;

class CacheEventMutexTest extends TestCase
{
    /**
     * @var \Illuminate\Console\Scheduling\CacheEventMutex
     */
    protected $cacheMutex;

    /**
     * @var \Illuminate\Console\Scheduling\Event
     */
    protected $event;

    /**
     * @var \Illuminate\Tests\Console\Fixtures\FakeCacheFactory
     */
    protected $cacheFactory;

    protected function setUp(): void
    {
        $this->cacheMutex = new CacheEventMutex($this->cacheFactory = new FakeCacheFactory);
        $this->event = new Event($this->cacheMutex, 'command');
    }

    protected function storageRepository()
    {
        return new Repository(new StorageStore(new ArrayFilesystem, 'cache'));
    }

    public function testPreventOverlap()
    {
        $this->cacheFactory->repository = $this->storageRepository();

        $this->assertTrue($this->cacheMutex->create($this->event));
    }

    public function testCustomConnection()
    {
        $this->cacheFactory->repository = $this->storageRepository();
        $this->cacheMutex->useStore('test');

        $this->assertTrue($this->cacheMutex->create($this->event));
        $this->assertSame('test', $this->cacheFactory->name);
    }

    public function testPreventOverlapFails()
    {
        $repository = $this->storageRepository();
        $repository->add($this->event->mutexName(), true, 60);
        $this->cacheFactory->repository = $repository;

        $this->assertFalse($this->cacheMutex->create($this->event));
    }

    public function testOverlapsForNonRunningTask()
    {
        $this->cacheFactory->repository = $this->storageRepository();

        $this->assertFalse($this->cacheMutex->exists($this->event));
    }

    public function testOverlapsForRunningTask()
    {
        $repository = $this->storageRepository();
        $repository->add($this->event->mutexName(), true, 60);
        $this->cacheFactory->repository = $repository;

        $this->assertTrue($this->cacheMutex->exists($this->event));
    }

    public function testResetOverlap()
    {
        $repository = $this->storageRepository();
        $repository->add($this->event->mutexName(), true, 60);
        $this->cacheFactory->repository = $repository;

        $this->cacheMutex->forget($this->event);

        $this->assertFalse($repository->has($this->event->mutexName()));
    }

    public function testPreventOverlapWithLockProvider()
    {
        $this->cacheFactory->repository = new Repository(new ArrayStore);

        $this->assertTrue($this->cacheMutex->create($this->event));
    }

    public function testPreventOverlapFailsWithLockProvider()
    {
        $this->cacheFactory->repository = new Repository(new ArrayStore);

        // first create the lock, so we can test that the next call fails.
        $this->cacheMutex->create($this->event);

        $this->assertFalse($this->cacheMutex->create($this->event));
    }

    public function testOverlapsForNonRunningTaskWithLockProvider()
    {
        $this->cacheFactory->repository = new Repository(new ArrayStore);

        $this->assertFalse($this->cacheMutex->exists($this->event));
    }

    public function testOverlapsForRunningTaskWithLockProvider()
    {
        $this->cacheFactory->repository = new Repository(new ArrayStore);

        $this->cacheMutex->create($this->event);

        $this->assertTrue($this->cacheMutex->exists($this->event));
    }

    public function testResetOverlapWithLockProvider()
    {
        $this->cacheFactory->repository = new Repository(new ArrayStore);

        $this->cacheMutex->create($this->event);

        $this->cacheMutex->forget($this->event);

        $this->assertFalse($this->cacheMutex->exists($this->event));
    }
}
