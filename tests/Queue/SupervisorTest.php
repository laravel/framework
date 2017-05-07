<?php

namespace Illuminate\Tests\Queue;

use Carbon\Carbon;
use Illuminate\Queue\Supervisor\Events\LoopBeginning;
use Illuminate\Queue\Supervisor\Events\LoopCompleting;
use Illuminate\Queue\Supervisor\Events\RunSucceed;
use Illuminate\Queue\Supervisor\Events\SupervisorStopping;
use Illuminate\Queue\Supervisor\Supervisor;

use Illuminate\Queue\Supervisor\SupervisorOptions;
use Illuminate\Queue\Supervisor\SupervisorState;
use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Contracts\Foundation\Application;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class SupervisorTest extends TestCase
{
    /**
     * @var MockInterface|Application
     */
    private $laravel;

    /**
     * @var MockInterface|Cache
     */
    private $cache;

    /**
     * @var MockInterface|Bus
     */
    private $bus;

    /**
     * @var MockInterface|Events
     */
    private $events;

    /**
     * @var MockInterface|ExceptionHandler
     */
    private $exceptions;

    /**
     * @var SupervisorStub
     */
    private $supervisor;

    public function setUp()
    {
        parent::setUp();

        $this->laravel = Mockery::mock(Application::class);
        $this->cache = Mockery::mock(Cache::class);
        $this->bus = Mockery::mock(Bus::class);
        $this->events = Mockery::mock(Events::class);
        $this->exceptions = Mockery::mock(ExceptionHandler::class);

        $this->supervisor = new SupervisorStub($this->laravel, $this->cache, $this->bus, $this->events, $this->exceptions);
    }

    public function tearDown()
    {
        Mockery::close();
        pcntl_alarm(0);
    }

    public function test_it_pause_when_app_is_down_then_stop_on_queue_restart()
    {
        $runs = 0;
        $this->laravel->shouldReceive('make')->once()->with(SupervisorState::class)->andReturn($state = new SupervisorState());
        $this->cache->shouldReceive('get')->with('illuminate:queue:restart')->once()->andReturn(null);
        $this->cache->shouldReceive('get')->with('illuminate:queue:restart')->once()->andReturn(Carbon::now());
        $this->laravel->shouldReceive('isDownForMaintenance')->once()->andReturn(true);
        $this->events->shouldReceive('dispatch')->once()->with(\Mockery::on(function ($event) {
            return $event instanceof SupervisorStopping && $event->status === 0;
        }))->andThrow(new \Exception('stopped'));
        try {
            $this->supervisor->supervise(function () use (&$runs) {
                $runs++;
            }, $options = new SupervisorOptions());
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertSame('stopped', $e->getMessage());
        }
        $this->assertEquals(0, $runs);
        $this->assertEquals(1, $this->supervisor->paused);
    }

    public function test_event_listeners_can_pause_and_stop_supervisor()
    {
        $runs = 0;
        $this->laravel->shouldReceive('make')->once()->with(SupervisorState::class)->andReturn($state = new SupervisorState());
        $this->laravel->shouldReceive('isDownForMaintenance')->once()->andReturn(false);
        $this->cache->shouldReceive('get')->twice()->with('illuminate:queue:restart')->andReturn(null);

        $this->events->shouldReceive('until')->once()->with(\Mockery::on(function ($event) {
            return $event instanceof LoopBeginning;
        }))->andReturn(false);

        $this->events->shouldReceive('until')->once()->with(\Mockery::on(function ($event) {
            return $event instanceof LoopCompleting;
        }))->andReturn(false);

        $this->events->shouldReceive('dispatch')->once()->with(\Mockery::on(function ($event) {
            return $event instanceof SupervisorStopping && $event->status === 0;
        }))->andThrow(new \Exception('stopped'));

        try {
            $this->supervisor->supervise(function () use (&$runs) {
                $runs++;
            }, $options = new SupervisorOptions());
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertSame('stopped', $e->getMessage());
        }
        $this->assertEquals(0, $runs);
        $this->assertEquals(1, $this->supervisor->paused);
    }

    public function test_it_runs_once_before_queue_restart()
    {
        $runs = 0;
        $this->laravel->shouldReceive('make')->once()->with(SupervisorState::class)->andReturn($state = new SupervisorState());
        $this->cache->shouldReceive('get')->with('illuminate:queue:restart')->once()->andReturn(null);
        $this->cache->shouldReceive('get')->with('illuminate:queue:restart')->once()->andReturn(Carbon::now());
        $this->laravel->shouldReceive('isDownForMaintenance')->once()->andReturn(false);

        $this->events->shouldReceive('until')->once()->with(\Mockery::on(function ($event) {
            return $event instanceof LoopBeginning;
        }))->andReturnNull();

        $this->events->shouldReceive('dispatch')->once()->with(\Mockery::on(function ($event) {
            return $event instanceof RunSucceed;
        }))->andReturnNull();

        $this->events->shouldReceive('dispatch')->once()->with(\Mockery::on(function ($event) {
            return $event instanceof SupervisorStopping && $event->status === 0;
        }))->andThrow(new \Exception('stopped'));
        try {
            $this->supervisor->supervise(function () use (&$runs) {
                $runs++;
            }, $options = new SupervisorOptions());
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertSame('stopped', $e->getMessage());
        }
        $this->assertEquals(1, $runs);
        $this->assertEquals(0, $this->supervisor->paused);
    }
}

class SupervisorStub extends Supervisor
{
    public $paused = 0;

    protected function kill($status = 0)
    {
        throw new \Exception('killed');
    }

    protected function pause()
    {
        $this->paused++;
    }
}
