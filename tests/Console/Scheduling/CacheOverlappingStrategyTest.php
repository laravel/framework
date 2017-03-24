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
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cacheRepository;

    public function setUp()
    {
        parent::setUp();

        $this->cacheRepository = m::mock('Illuminate\Contracts\Cache\Repository');
        $this->cacheMutex = new CacheMutex($this->cacheRepository);
    }

    public function testPreventOverlap()
    {
        $cacheMutex = $this->cacheMutex;

        $this->cacheRepository->shouldReceive('add');

        $event = new Event($this->cacheMutex, 'command');

        $cacheMutex->create($event);
    }

    public function testPreventOverlapFails()
    {
        $cacheMutex = $this->cacheMutex;

        $this->cacheRepository->shouldReceive('add')->andReturn(false);

        $event = new Event($this->cacheMutex, 'command');

        $this->assertFalse($cacheMutex->create($event));
    }

    public function testOverlapsForNonRunningTask()
    {
        $cacheMutex = $this->cacheMutex;

        $this->cacheRepository->shouldReceive('has')->andReturn(false);

        $event = new Event($this->cacheMutex, 'command');

        $this->assertFalse($cacheMutex->exists($event));
    }

    public function testOverlapsForRunningTask()
    {
        $cacheMutex = $this->cacheMutex;

        $this->cacheRepository->shouldReceive('has')->andReturn(true);

        $event = new Event($this->cacheMutex, 'command');

        $this->assertTrue($cacheMutex->exists($event));
    }

    public function testResetOverlap()
    {
        $cacheMutex = $this->cacheMutex;

        $this->cacheRepository->shouldReceive('forget');

        $event = new Event($this->cacheMutex, 'command');

        $cacheMutex->forget($event);
    }
}
