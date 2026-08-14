<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Foundation\Bus\PendingDispatch;
use Mockery;
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
        $this->job = Mockery::mock(stdClass::class);
        $this->pendingDispatch = new PendingDispatchWithoutDestructor($this->job);
    }

    public function testOnConnection()
    {
        $this->job->expects('onConnection')->with('test-connection');
        $this->pendingDispatch->onConnection('test-connection');
    }

    public function testOnQueue()
    {
        $this->job->expects('onQueue')->with('test-queue');
        $this->pendingDispatch->onQueue('test-queue');
    }

    public function testAllOnConnection()
    {
        $this->job->expects('allOnConnection')->with('test-connection');
        $this->pendingDispatch->allOnConnection('test-connection');
    }

    public function testAllOnQueue()
    {
        $this->job->expects('allOnQueue')->with('test-queue');
        $this->pendingDispatch->allOnQueue('test-queue');
    }

    public function testDelay()
    {
        $this->job->expects('delay')->with(60);
        $this->pendingDispatch->delay(60);
    }

    public function testWithoutDelay()
    {
        $this->job->expects('withoutDelay');
        $this->pendingDispatch->withoutDelay();
    }

    public function testAfterCommit()
    {
        $this->job->expects('afterCommit');
        $this->pendingDispatch->afterCommit();
    }

    public function testBeforeCommit()
    {
        $this->job->expects('beforeCommit');
        $this->pendingDispatch->beforeCommit();
    }

    public function testChain()
    {
        $chain = [new stdClass];
        $this->job->expects('chain')->with($chain);
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
        $newJob = Mockery::mock(stdClass::class);
        $this->job->expects('appendToChain')->with($newJob);
        $this->pendingDispatch->appendToChain($newJob);
    }

    public function testWhenMethodOfConditionableTraitWithTrue()
    {
        $this->job->expects('delay')->with(300);

        $this->pendingDispatch->when(true, fn ($pendingDispatch) => $pendingDispatch->delay(300));
    }

    public function testWhenMethodOfConditionableTraitWithFalse()
    {
        $this->job->shouldReceive('delay')->never();

        $this->pendingDispatch->when(false, fn ($pendingDispatch) => $pendingDispatch->delay(300));
    }

    public function testUnlessMethodOfConditionableTraitWithTrue()
    {
        $this->job->shouldReceive('delay')->never();

        $this->pendingDispatch->unless(true, fn ($pendingDispatch) => $pendingDispatch->delay(300));
    }

    public function testUnlessMethodOfConditionableTraitWithFalse()
    {
        $this->job->expects('delay')->with(300);

        $this->pendingDispatch->unless(false, fn ($pendingDispatch) => $pendingDispatch->delay(300));
    }
}
