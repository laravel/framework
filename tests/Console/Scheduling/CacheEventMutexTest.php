<?php

namespace Illuminate\Tests\Console\Scheduling;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\CacheEventMutex;

class CacheEventMutexTest extends TestCase
{
    /**
     * @var CacheEventMutex
     */
    protected $cacheMutex;

    /**
     * @var Event
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
        $this->cacheMutex = new CacheEventMutex($this->cacheRepository);
        $this->event = new Event($this->cacheMutex, 'command');
    }

    public function testPreventOverlap()
    {
        $this->cacheRepository->shouldReceive('add')->once();

        $this->cacheMutex->create($this->event);
    }

    public function testPreventOverlapFails()
    {
        $this->cacheRepository->shouldReceive('add')->once()->andReturn(false);

        $this->assertFalse($this->cacheMutex->create($this->event));
    }

    public function testOverlapsForNonRunningTask()
    {
        $this->cacheRepository->shouldReceive('has')->once()->andReturn(false);

        $this->assertFalse($this->cacheMutex->exists($this->event));
    }

    public function testOverlapsForRunningTask()
    {
        $this->cacheRepository->shouldReceive('has')->once()->andReturn(true);

        $this->assertTrue($this->cacheMutex->exists($this->event));
    }

    public function testResetOverlap()
    {
        $this->cacheRepository->shouldReceive('forget')->once();

        $this->cacheMutex->forget($this->event);
    }
}
