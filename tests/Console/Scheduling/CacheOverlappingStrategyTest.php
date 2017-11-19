<?php

namespace Illuminate\Tests\Console\Scheduling;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\CacheMutex;

class CacheMutexTest extends TestCase
{
    /**
     * @var CacheMutex
     */
    protected $cacheMutex;

    /**
     * @var \Illuminate\Console\Scheduling\Event
     */
    protected $event;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cacheRepository;

    public function setUp()
    {
        parent::setUp();

        $this->cacheRepository = m::mock('Illuminate\Contracts\Cache\Repository');
        $this->cacheMutex = new CacheMutex($this->cacheRepository);
        $this->event = new Event($this->cacheMutex, 'command');
    }

    public function testPreventOverlap()
    {
        $this->cacheRepository->shouldReceive('add')->with($this->event->mutexName().date('Hi'), true, 1)->andReturn(true);
        $this->cacheRepository->shouldReceive('add')->with($this->event->mutexName(), true, $this->event->expiresAt)->andReturn(true);

        $this->cacheMutex->create($this->event);
    }

    public function testPreventOverlapFailsDueToTaskRunningThisMinute()
    {
        $this->cacheRepository->shouldReceive('add')->with($this->event->mutexName().date('Hi'), true, 1)->andReturn(false);
        $this->cacheRepository->shouldReceive('add')->with($this->event->mutexName(), true, $this->event->expiresAt)->never();

        $this->assertFalse($this->cacheMutex->create($this->event));
    }

    public function testPreventOverlapFailsDueToTaskStillRunning()
    {
        $this->cacheRepository->shouldReceive('add')->with($this->event->mutexName().date('Hi'), true, 1)->andReturn(true);
        $this->cacheRepository->shouldReceive('add')->with($this->event->mutexName(), true, $this->event->expiresAt)->andReturn(false);

        $this->assertFalse($this->cacheMutex->create($this->event));
    }

    public function testOverlapsForNonRunningTaskThatHasNotRunThisMinute()
    {
        $this->cacheRepository->shouldReceive('has')->with($this->event->mutexName())->andReturn(false);
        $this->cacheRepository->shouldReceive('has')->with($this->event->mutexName().date('Hi'))->andReturn(false);

        $this->assertFalse($this->cacheMutex->exists($this->event));
    }

    public function testOverlapsForRunningTaskOncePerMinutue()
    {
        $this->cacheRepository->shouldReceive('has')->with($this->event->mutexName())->andReturn(true);
        $this->cacheRepository->shouldReceive('has')->with($this->event->mutexName().date('Hi'))->never();

        $this->assertTrue($this->cacheMutex->exists($this->event));
    }

    public function testOverlapsForRunningTaskLongerThanMinute()
    {
        $this->cacheRepository->shouldReceive('has')->with($this->event->mutexName())->andReturn(false);
        $this->cacheRepository->shouldReceive('has')->with($this->event->mutexName().date('Hi'))->andReturn(true);

        $this->assertTrue($this->cacheMutex->exists($this->event));
    }

    public function testResetOverlap()
    {
        $this->cacheRepository->shouldReceive('forget')->once();

        $this->cacheMutex->forget($this->event);
    }
}
