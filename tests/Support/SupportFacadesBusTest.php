<?php

namespace Illuminate\Tests\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\PendingClosureDispatch;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Bus;
use Orchestra\Testbench\TestCase;

class SupportFacadesBusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    public function testDispatchPendingReturnsCorrectTypeForJob()
    {
        $result = Bus::dispatchPending(new BusFacadeJobStub);

        $this->assertInstanceOf(PendingDispatch::class, $result);
    }

    public function testDispatchPendingReturnsCorrectTypeForClosure()
    {
        $result = Bus::dispatchPending(function () {
            return 'test';
        });

        $this->assertInstanceOf(PendingClosureDispatch::class, $result);
    }

    public function testDispatchPendingReturnsPendingDispatchWithCorrectJob()
    {
        $job = new BusFacadeJobStub;

        $result = Bus::dispatchPending($job);

        $this->assertSame($job, $result->getJob());
    }

    public function testDispatchPendingDispatchesJob()
    {
        Bus::dispatchPending(new BusFacadeJobStub);

        Bus::assertDispatched(BusFacadeJobStub::class);
    }
}

class BusFacadeJobStub implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }
}
