<?php

namespace Illuminate\Tests\Bus;

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

    /**
     * @var PendingDispatchWithoutDestructor
     */
    protected $pendingDispatch;

    protected function setUp(): void
    {
        $this->job = m::mock(stdClass::class);
        $this->pendingDispatch = new PendingDispatchWithoutDestructor($this->job);

        parent::setUp();
    }

    public function testOnConnection(): void
    {
        $this->job->shouldReceive('onConnection')->once()->with('test-connection');
        $this->pendingDispatch->onConnection('test-connection');
    }

    public function testOnQueue(): void
    {
        $this->job->shouldReceive('onQueue')->once()->with('test-queue');
        $this->pendingDispatch->onQueue('test-queue');
    }

    public function testAllOnConnection(): void
    {
        $this->job->shouldReceive('allOnConnection')->once()->with('test-connection');
        $this->pendingDispatch->allOnConnection('test-connection');
    }

    public function testAllOnQueue(): void
    {
        $this->job->shouldReceive('allOnQueue')->once()->with('test-queue');
        $this->pendingDispatch->allOnQueue('test-queue');
    }

    public function testDelay(): void
    {
        $this->job->shouldReceive('delay')->once()->with(60);
        $this->pendingDispatch->delay(60);
    }

    public function testWithoutDelay(): void
    {
        $this->job->shouldReceive('withoutDelay')->once();
        $this->pendingDispatch->withoutDelay();
    }

    public function testAfterCommit(): void
    {
        $this->job->shouldReceive('afterCommit')->once();
        $this->pendingDispatch->afterCommit();
    }

    public function testBeforeCommit(): void
    {
        $this->job->shouldReceive('beforeCommit')->once();
        $this->pendingDispatch->beforeCommit();
    }

    public function testChain(): void
    {
        $chain = [new stdClass];
        $this->job->shouldReceive('chain')->once()->with($chain);
        $this->pendingDispatch->chain($chain);
    }

    public function testAfterResponse(): void
    {
        $this->pendingDispatch->afterResponse();
        $this->assertTrue(
            (new ReflectionClass($this->pendingDispatch))->getProperty('afterResponse')->getValue($this->pendingDispatch)
        );
    }

    public function testGetJob(): void
    {
        $this->assertSame($this->job, $this->pendingDispatch->getJob());
    }

    public function testDynamicallyProxyMethods(): void
    {
        $newJob = m::mock(stdClass::class);
        $this->job->shouldReceive('appendToChain')->once()->with($newJob);
        $this->pendingDispatch->appendToChain($newJob);
    }
}
