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

    public function testPreventEventOverlap()
    {
        $this->cacheRepository->shouldReceive('add')->once()->with($this->event->mutexName(), true, $this->event->expiresAt)->andReturn(true);

        $this->cacheMutex->create($this->event);
    }

    public function testPreventEventOverlapFails()
    {
        $this->cacheRepository->shouldReceive('add')->once()->with($this->event->mutexName(), true, $this->event->expiresAt)->andReturn(false);

        $this->assertFalse($this->cacheMutex->create($this->event));
    }

    public function testEventOverlapsForNonRunningTask()
    {
        $this->cacheRepository->shouldReceive('has')->once()->with($this->event->mutexName())->andReturn(false);

        $this->assertFalse($this->cacheMutex->exists($this->event));
    }

    public function testEventOverlapsForRunningTask()
    {
        $this->cacheRepository->shouldReceive('has')->once()->with($this->event->mutexName())->andReturn(true);

        $this->assertTrue($this->cacheMutex->exists($this->event));
    }

    public function testEventResetOverlap()
    {
        $this->cacheRepository->shouldReceive('forget')->with($this->event->mutexName())->once();

        $this->cacheMutex->forget($this->event);
    }

    public function testPreventServerOverlap()
    {
        $this->cacheRepository->shouldReceive('add')->once()->with($this->event->mutexName().$this->event->timestamp->format('Hi'), true, 60)->andReturn(true);

        $this->cacheMutex->serverCreate($this->event);
    }

    public function testPreventServerOverlapFails()
    {
        $this->cacheRepository->shouldReceive('add')->once()->with($this->event->mutexName().$this->event->timestamp->format('Hi'), true, 60)->andReturn(false);

        $this->assertFalse($this->cacheMutex->serverCreate($this->event));
    }

    public function testServerDetectsRunEvent()
    {
        $this->cacheRepository->shouldReceive('has')->once()->with($this->event->mutexName().$this->event->timestamp->format('Hi'))->andReturn(true);

        $this->cacheMutex->serverExists($this->event);
    }

    public function testServerDetectsFirstAttempt()
    {
        $this->cacheRepository->shouldReceive('has')->once()->with($this->event->mutexName().$this->event->timestamp->format('Hi'))->andReturn(false);

        $this->assertFalse($this->cacheMutex->serverExists($this->event));
    }
}
