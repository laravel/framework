<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Foundation\Queue\Queueable;
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
        $this->job = new PendingDispatchTestJob;
        $this->pendingDispatch = new PendingDispatchWithoutDestructor($this->job);
    }

    public function testOnConnection()
    {
        $this->pendingDispatch->onConnection('test-connection');

        $this->assertSame('test-connection', $this->job->connection);
    }

    public function testOnQueue()
    {
        $this->pendingDispatch->onQueue('test-queue');

        $this->assertSame('test-queue', $this->job->queue);
    }

    public function testAllOnConnection()
    {
        $this->pendingDispatch->allOnConnection('test-connection');

        $this->assertSame('test-connection', $this->job->connection);
        $this->assertSame('test-connection', $this->job->chainConnection);
    }

    public function testAllOnQueue()
    {
        $this->pendingDispatch->allOnQueue('test-queue');

        $this->assertSame('test-queue', $this->job->queue);
        $this->assertSame('test-queue', $this->job->chainQueue);
    }

    public function testDelay()
    {
        $this->pendingDispatch->delay(60);

        $this->assertSame(60, $this->job->delay);
    }

    public function testWithoutDelay()
    {
        $this->pendingDispatch->delay(60)->withoutDelay();

        $this->assertSame(0, $this->job->delay);
    }

    public function testAfterCommit()
    {
        $this->pendingDispatch->afterCommit();

        $this->assertTrue($this->job->afterCommit);
    }

    public function testBeforeCommit()
    {
        $this->pendingDispatch->beforeCommit();

        $this->assertFalse($this->job->afterCommit);
    }

    public function testChain()
    {
        $this->pendingDispatch->chain([new stdClass]);

        $this->assertSame([serialize(new stdClass)], $this->job->chained);
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
        $this->pendingDispatch->appendToChain(new stdClass);

        $this->assertSame([serialize(new stdClass)], $this->job->chained);
    }

    public function testWhenMethodOfConditionableTraitWithTrue()
    {
        $this->pendingDispatch->when(true, fn ($pendingDispatch) => $pendingDispatch->delay(300));

        $this->assertSame(300, $this->job->delay);
    }

    public function testWhenMethodOfConditionableTraitWithFalse()
    {
        $this->pendingDispatch->when(false, fn ($pendingDispatch) => $pendingDispatch->delay(300));

        $this->assertNull($this->job->delay);
    }

    public function testUnlessMethodOfConditionableTraitWithTrue()
    {
        $this->pendingDispatch->unless(true, fn ($pendingDispatch) => $pendingDispatch->delay(300));

        $this->assertNull($this->job->delay);
    }

    public function testUnlessMethodOfConditionableTraitWithFalse()
    {
        $this->pendingDispatch->unless(false, fn ($pendingDispatch) => $pendingDispatch->delay(300));

        $this->assertSame(300, $this->job->delay);
    }
}

class PendingDispatchTestJob
{
    use Queueable;
}
