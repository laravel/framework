<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Cache\StorageStore;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\CacheSchedulingMutex;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Tests\Cache\Fixtures\ArrayFilesystem;
use PHPUnit\Framework\TestCase;

class CacheSchedulingMutexTest extends TestCase
{
    /**
     * @var \Illuminate\Console\Scheduling\CacheSchedulingMutex
     */
    protected $cacheMutex;

    /**
     * @var \Illuminate\Console\Scheduling\Event
     */
    protected $event;

    /**
     * @var \Illuminate\Support\Carbon
     */
    protected $time;

    /**
     * @var \Illuminate\Tests\Console\Scheduling\CacheSchedulingMutexTestFactory
     */
    protected $cacheFactory;

    protected function setUp(): void
    {
        $this->cacheMutex = new CacheSchedulingMutex($this->cacheFactory = new CacheSchedulingMutexTestFactory);
        $this->event = new Event(new CacheEventMutex($this->cacheFactory), 'command');
        $this->time = Carbon::now();
    }

    protected function storageRepository()
    {
        return new Repository(new StorageStore(new ArrayFilesystem, 'cache'));
    }

    protected function mutexName()
    {
        return $this->event->mutexName().$this->time->format('Hi');
    }

    public function testMutexReceivesCorrectCreate()
    {
        $this->cacheFactory->repository = $this->storageRepository();

        $this->assertTrue($this->cacheMutex->create($this->event, $this->time));
        $this->assertTrue($this->cacheFactory->repository->has($this->mutexName()));
    }

    public function testCanUseCustomConnection()
    {
        $this->cacheFactory->repository = $this->storageRepository();
        $this->cacheMutex->useStore('test');

        $this->assertTrue($this->cacheMutex->create($this->event, $this->time));
        $this->assertSame('test', $this->cacheFactory->name);
    }

    public function testPreventsMultipleRuns()
    {
        $repository = $this->storageRepository();
        $repository->add($this->mutexName(), true, 3600);
        $this->cacheFactory->repository = $repository;

        $this->assertFalse($this->cacheMutex->create($this->event, $this->time));
    }

    public function testChecksForNonRunSchedule()
    {
        $this->cacheFactory->repository = $this->storageRepository();

        $this->assertFalse($this->cacheMutex->exists($this->event, $this->time));
    }

    public function testChecksForAlreadyRunSchedule()
    {
        $repository = $this->storageRepository();
        $repository->add($this->mutexName(), true, 3600);
        $this->cacheFactory->repository = $repository;

        $this->assertTrue($this->cacheMutex->exists($this->event, $this->time));
    }

    public function testMutexReceivesCorrectCreateWithLockProvider()
    {
        $this->cacheFactory->repository = new Repository(new ArrayStore);

        $this->assertTrue($this->cacheMutex->create($this->event, $this->time));
    }

    public function testPreventsMultipleRunsWithLockProvider()
    {
        $this->cacheFactory->repository = new Repository(new ArrayStore);

        // first create the lock, so we can test that the next call fails.
        $this->cacheMutex->create($this->event, $this->time);

        $this->assertFalse($this->cacheMutex->create($this->event, $this->time));
    }

    public function testChecksForNonRunScheduleWithLockProvider()
    {
        $this->cacheFactory->repository = new Repository(new ArrayStore);

        $this->assertFalse($this->cacheMutex->exists($this->event, $this->time));
    }

    public function testChecksForAlreadyRunScheduleWithLockProvider()
    {
        $this->cacheFactory->repository = new Repository(new ArrayStore);

        $this->cacheMutex->create($this->event, $this->time);

        $this->assertTrue($this->cacheMutex->exists($this->event, $this->time));
    }
}

class CacheSchedulingMutexTestFactory implements Factory
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    public $repository;

    /**
     * The last store name that was requested.
     *
     * @var string|null
     */
    public $name;

    public function store($name = null)
    {
        $this->name = $name;

        return $this->repository;
    }
}
