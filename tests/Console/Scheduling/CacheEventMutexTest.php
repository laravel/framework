<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Cache\ArrayStore;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Mockery;
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
     * @var \Illuminate\Contracts\Cache\Factory
     */
    protected $cacheFactory;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cacheRepository;

    protected function setUp(): void
    {
        $this->cacheFactory = Mockery::mock(Factory::class);
        $this->cacheRepository = Mockery::mock(Repository::class);
        $this->cacheFactory->shouldReceive('store')->andReturn($this->cacheRepository);
        $this->cacheMutex = new CacheEventMutex($this->cacheFactory);
        $this->event = new Event($this->cacheMutex, 'command');
    }

    public function testPreventOverlap()
    {
        $this->cacheRepository->expects('getStore')->andReturn(new \stdClass);
        $this->cacheRepository->expects('add');

        $this->cacheMutex->create($this->event);
    }

    public function testCustomConnection()
    {
        $this->cacheRepository->expects('getStore')->andReturn(new \stdClass);
        $this->cacheRepository->expects('add');
        $this->cacheMutex->useStore('test');

        $this->cacheMutex->create($this->event);
    }

    public function testPreventOverlapFails()
    {
        $this->cacheRepository->expects('getStore')->andReturn(new \stdClass);
        $this->cacheRepository->expects('add')->andReturn(false);

        $this->assertFalse($this->cacheMutex->create($this->event));
    }

    public function testOverlapsForNonRunningTask()
    {
        $this->cacheRepository->expects('getStore')->andReturn(new \stdClass);
        $this->cacheRepository->expects('has')->andReturn(false);

        $this->assertFalse($this->cacheMutex->exists($this->event));
    }

    public function testOverlapsForRunningTask()
    {
        $this->cacheRepository->expects('getStore')->andReturn(new \stdClass);
        $this->cacheRepository->expects('has')->andReturn(true);

        $this->assertTrue($this->cacheMutex->exists($this->event));
    }

    public function testResetOverlap()
    {
        $this->cacheRepository->expects('getStore')->andReturn(new \stdClass);
        $this->cacheRepository->expects('forget');

        $this->cacheMutex->forget($this->event);
    }

    public function testPreventOverlapWithLockProvider()
    {
        $this->cacheRepository->expects('getStore')->times(2)->andReturn(new ArrayStore);

        $this->assertTrue($this->cacheMutex->create($this->event));
    }

    public function testPreventOverlapFailsWithLockProvider()
    {
        $this->cacheRepository->expects('getStore')->times(4)->andReturn(new ArrayStore);

        // first create the lock, so we can test that the next call fails.
        $this->cacheMutex->create($this->event);

        $this->assertFalse($this->cacheMutex->create($this->event));
    }

    public function testOverlapsForNonRunningTaskWithLockProvider()
    {
        $this->cacheRepository->expects('getStore')->times(2)->andReturn(new ArrayStore);

        $this->assertFalse($this->cacheMutex->exists($this->event));
    }

    public function testOverlapsForRunningTaskWithLockProvider()
    {
        $this->cacheRepository->expects('getStore')->times(4)->andReturn(new ArrayStore);

        $this->cacheMutex->create($this->event);

        $this->assertTrue($this->cacheMutex->exists($this->event));
    }

    public function testResetOverlapWithLockProvider()
    {
        $this->cacheRepository->expects('getStore')->times(6)->andReturn(new ArrayStore);

        $this->cacheMutex->create($this->event);

        $this->cacheMutex->forget($this->event);

        $this->assertFalse($this->cacheMutex->exists($this->event));
    }
}
