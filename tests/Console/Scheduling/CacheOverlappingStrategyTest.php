<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\CacheOverlappingStrategy;
use Illuminate\Console\Scheduling\Event;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class CacheOverlappingStrategyTest extends TestCase
{
    /**
     * @var CacheOverlappingStrategy
     */
    protected $cacheOverlappingStrategy;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cacheRepository;

    public function setUp()
    {
        parent::setUp();

        $this->cacheRepository = m::mock('Illuminate\Contracts\Cache\Repository');
        $this->cacheOverlappingStrategy = new CacheOverlappingStrategy($this->cacheRepository);
    }

    public function testPreventOverlap()
    {
        $cacheOverlappingStrategy = $this->cacheOverlappingStrategy;

        $this->cacheRepository->shouldReceive('put');

        $event = new Event($this->cacheOverlappingStrategy, 'command');

        $cacheOverlappingStrategy->prevent($event);
    }

    public function testOverlapsForNonRunningTask()
    {
        $cacheOverlappingStrategy = $this->cacheOverlappingStrategy;

        $this->cacheRepository->shouldReceive('has')->andReturn(false);

        $event = new Event($this->cacheOverlappingStrategy, 'command');

        $this->assertFalse($cacheOverlappingStrategy->overlaps($event));
    }

    public function testOverlapsForRunningTask()
    {
        $cacheOverlappingStrategy = $this->cacheOverlappingStrategy;

        $this->cacheRepository->shouldReceive('has')->andReturn(true);

        $event = new Event($this->cacheOverlappingStrategy, 'command');

        $this->assertTrue($cacheOverlappingStrategy->overlaps($event));
    }

    public function testResetOverlap()
    {
        $cacheOverlappingStrategy = $this->cacheOverlappingStrategy;

        $this->cacheRepository->shouldReceive('forget');

        $event = new Event($this->cacheOverlappingStrategy, 'command');

        $cacheOverlappingStrategy->reset($event);
    }
}
