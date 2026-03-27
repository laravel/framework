<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Fluent;
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

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
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

    public function testSpreadWithDelay()
    {
        $job = new Fluent;
        $pendingDispatch = new PendingDispatchWithoutDestructor($job);
        $limit = Limit::perSecond(1, 15);

        $pendingDispatch->spreadWithDelay($limit, 0);
        $this->assertSame(0, $job->delay);
        $pendingDispatch->spreadWithDelay($limit, 1);
        $this->assertSame(15, $job->delay);
        $pendingDispatch->spreadWithDelay($limit, 2);
        $this->assertSame(30, $job->delay);
        $pendingDispatch->spreadWithDelay($limit, 3);
        $this->assertSame(45, $job->delay);
        $pendingDispatch->spreadWithDelay($limit, 4);
        $this->assertSame(60, $job->delay);
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
}
