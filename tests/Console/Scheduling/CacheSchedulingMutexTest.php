<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Cache\ArrayStore;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\CacheSchedulingMutex;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use Mockery;
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
        $this->cacheMutex = new CacheSchedulingMutex($this->cacheFactory);
        $this->event = new Event(new CacheEventMutex($this->cacheFactory), 'command');
        $this->time = Carbon::now();
    }

    public function testMutexReceivesCorrectCreate()
    {
        $this->cacheRepository->expects('getStore')->andReturn(new \stdClass);
        $this->cacheRepository->expects('add')->with($this->event->mutexName().$this->time->format('Hi'), true, 3600)->andReturn(true);

        $this->assertTrue($this->cacheMutex->create($this->event, $this->time));
    }

    public function testCanUseCustomConnection()
    {
        $this->cacheRepository->expects('getStore')->andReturn(new \stdClass);
        $this->cacheRepository->expects('add')->with($this->event->mutexName().$this->time->format('Hi'), true, 3600)->andReturn(true);
        $this->cacheMutex->useStore('test');

        $this->assertTrue($this->cacheMutex->create($this->event, $this->time));
    }

    public function testPreventsMultipleRuns()
    {
        $this->cacheRepository->expects('getStore')->andReturn(new \stdClass);
        $this->cacheRepository->expects('add')->with($this->event->mutexName().$this->time->format('Hi'), true, 3600)->andReturn(false);

        $this->assertFalse($this->cacheMutex->create($this->event, $this->time));
    }

    public function testChecksForNonRunSchedule()
    {
        $this->cacheRepository->expects('getStore')->andReturn(new \stdClass);
        $this->cacheRepository->expects('has')->with($this->event->mutexName().$this->time->format('Hi'))->andReturn(false);

        $this->assertFalse($this->cacheMutex->exists($this->event, $this->time));
    }

    public function testChecksForAlreadyRunSchedule()
    {
        $this->cacheRepository->expects('getStore')->andReturn(new \stdClass);
        $this->cacheRepository->expects('has')->with($this->event->mutexName().$this->time->format('Hi'))->andReturn(true);

        $this->assertTrue($this->cacheMutex->exists($this->event, $this->time));
    }

    public function testMutexReceivesCorrectCreateWithLockProvider()
    {
        $this->cacheRepository->expects('getStore')->times(2)->andReturn(new ArrayStore);

        $this->assertTrue($this->cacheMutex->create($this->event, $this->time));
    }

    public function testPreventsMultipleRunsWithLockProvider()
    {
        $this->cacheRepository->expects('getStore')->times(4)->andReturn(new ArrayStore);

        // first create the lock, so we can test that the next call fails.
        $this->cacheMutex->create($this->event, $this->time);

        $this->assertFalse($this->cacheMutex->create($this->event, $this->time));
    }

    public function testChecksForNonRunScheduleWithLockProvider()
    {
        $this->cacheRepository->expects('getStore')->times(2)->andReturn(new ArrayStore);

        $this->assertFalse($this->cacheMutex->exists($this->event, $this->time));
    }

    public function testChecksForAlreadyRunScheduleWithLockProvider()
    {
        $this->cacheRepository->expects('getStore')->times(4)->andReturn(new ArrayStore);

        $this->cacheMutex->create($this->event, $this->time);

        $this->assertTrue($this->cacheMutex->exists($this->event, $this->time));
    }
}
