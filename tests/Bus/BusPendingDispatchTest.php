<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Bus\PendingDispatch;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class PendingDispatchWithoutDestructor extends PendingDispatch
{
    public function __destruct()
    {
        // Prevent the job from being dispatched
    }
}

class BusPendingDispatchTest extends TestCase
{
    protected $job;

    protected $dispatcher;

    /**
     * @var PendingDispatchWithoutDestructor
     */
    protected $pendingDispatch;

    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $this->dispatcher = m::mock(Dispatcher::class);
        $container->instance(Dispatcher::class, $this->dispatcher);
        Container::setInstance($container);

        $this->job = m::mock(stdClass::class);
        $this->pendingDispatch = new PendingDispatchWithoutDestructor($this->job);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Container::setInstance(null);
    }

    public function testOnConnection()
    {
        $this->job->shouldReceive('onConnection')->once()->with('test-connection');
        $this->pendingDispatch->onConnection('test-connection');
    }

    public function testOnQueue()
    {
        $this->job->shouldReceive('onQueue')->once()->with('test-queue');
        $this->pendingDispatch->onQueue('test-queue');
    }

    public function testAllOnConnection()
    {
        $this->job->shouldReceive('allOnConnection')->once()->with('test-connection');
        $this->pendingDispatch->allOnConnection('test-connection');
    }

    public function testAllOnQueue()
    {
        $this->job->shouldReceive('allOnQueue')->once()->with('test-queue');
        $this->pendingDispatch->allOnQueue('test-queue');
    }

    public function testDelay()
    {
        $this->job->shouldReceive('delay')->once()->with(60);
        $this->pendingDispatch->delay(60);
    }

    public function testWithoutDelay()
    {
        $this->job->shouldReceive('withoutDelay')->once();
        $this->pendingDispatch->withoutDelay();
    }

    public function testAfterCommit()
    {
        $this->job->shouldReceive('afterCommit')->once();
        $this->pendingDispatch->afterCommit();
    }

    public function testBeforeCommit()
    {
        $this->job->shouldReceive('beforeCommit')->once();
        $this->pendingDispatch->beforeCommit();
    }

    public function testChain()
    {
        $chain = [new stdClass];
        $this->job->shouldReceive('chain')->once()->with($chain);
        $this->pendingDispatch->chain($chain);
    }

    public function testAfterResponse()
    {
        $this->pendingDispatch->afterResponse();
        $this->assertTrue(
            (new ReflectionClass($this->pendingDispatch))->getProperty('afterResponse')->getValue($this->pendingDispatch)
        );
    }

    public function testGetJob()
    {
        $this->assertSame($this->job, $this->pendingDispatch->getJob());
    }

    public function testDynamicallyProxyMethods()
    {
        $newJob = m::mock(stdClass::class);
        $this->job->shouldReceive('appendToChain')->once()->with($newJob);
        $this->pendingDispatch->appendToChain($newJob);
    }

    public function testCancelPendingDispatch()
    {
        $result = $this->pendingDispatch->cancelPendingDispatch();

        $this->assertSame($this->pendingDispatch, $result);
        $this->assertFalse(
            (new ReflectionClass($this->pendingDispatch))->getProperty('pendingDispatch')->getValue($this->pendingDispatch)
        );
    }

    public function testFlushPendingDispatchAfterCancelReturnsEarly()
    {
        $this->dispatcher->shouldNotReceive('dispatch');

        $this->pendingDispatch->cancelPendingDispatch();
        $result = $this->pendingDispatch->flushPendingDispatch();

        $this->assertNull($result);
    }

    public function testFlushPendingDispatchSetsPendingDispatchToFalse()
    {
        $this->dispatcher->shouldReceive('dispatch')->once()->with($this->job);

        $this->pendingDispatch->flushPendingDispatch();

        $this->assertFalse(
            (new ReflectionClass($this->pendingDispatch))->getProperty('pendingDispatch')->getValue($this->pendingDispatch)
        );
    }

    public function testFlushPendingDispatchReturnsEarlyIfAlreadyDispatched()
    {
        $this->dispatcher->shouldReceive('dispatch')->once()->with($this->job);

        $this->pendingDispatch->flushPendingDispatch();
        $result = $this->pendingDispatch->flushPendingDispatch();

        $this->assertNull($result);
    }

    public function testCancelPendingDispatchAfterFlushReturnsEarly()
    {
        $this->dispatcher->shouldReceive('dispatch')->once()->with($this->job);

        $this->pendingDispatch->flushPendingDispatch();
        $result = $this->pendingDispatch->cancelPendingDispatch();

        $this->assertSame($this->pendingDispatch, $result);
    }

    public function testFlushPendingDispatchWithDelay()
    {
        $this->job->shouldReceive('delay')->once()->with(5);
        $this->dispatcher->shouldReceive('dispatch')->once()->with($this->job);

        $this->pendingDispatch->delay(5)->flushPendingDispatch();
    }

    public function testCancelPendingDispatchWithDelay()
    {
        $this->job->shouldReceive('delay')->once()->with(5);
        $this->dispatcher->shouldNotReceive('dispatch');

        $this->pendingDispatch->delay(5)->cancelPendingDispatch();
    }

    public function testFlushPendingDispatchWithOnQueue()
    {
        $this->job->shouldReceive('onQueue')->once()->with('high');
        $this->dispatcher->shouldReceive('dispatch')->once()->with($this->job);

        $this->pendingDispatch->onQueue('high')->flushPendingDispatch();
    }

    public function testFlushPendingDispatchWithChainedOptions()
    {
        $this->job->shouldReceive('onConnection')->once()->with('redis');
        $this->job->shouldReceive('onQueue')->once()->with('high');
        $this->job->shouldReceive('delay')->once()->with(10);
        $this->dispatcher->shouldReceive('dispatch')->once()->with($this->job);

        $this->pendingDispatch
            ->onConnection('redis')
            ->onQueue('high')
            ->delay(10)
            ->flushPendingDispatch();
    }
}
