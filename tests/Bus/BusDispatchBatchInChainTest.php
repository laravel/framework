<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\PendingBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Queue\InteractsWithQueue;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BusDispatchBatchInChainTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);

        m::close();

        parent::tearDown();
    }

    public function test_dispatches_batch_and_clears_chain()
    {
        $container = new Container;
        Container::setInstance($container);

        $dispatcher = m::mock(Dispatcher::class);
        $container->instance(Dispatcher::class, $dispatcher);

        $job = new DispatchBatchInChainTestJob;
        $nextJob = new DispatchBatchInChainTestJob;
        $job->chained = [serialize($nextJob)];
        $job->chainConnection = 'test-connection';
        $job->chainQueue = 'test-queue';
        $job->chainCatchCallbacks = [];

        $batch = m::mock(Batch::class);
        $pendingBatch = m::mock(PendingBatch::class);

        $pendingBatch->shouldReceive('catch')->never();
        $pendingBatch->shouldReceive('finally')->once();
        $pendingBatch->shouldReceive('dispatch')->once()->andReturn($batch);

        $result = $job->dispatchBatchInChain($pendingBatch);

        $this->assertSame($batch, $result);
        $this->assertEmpty($job->chained);
    }

    public function test_does_not_dispatch_next_job_when_batch_is_cancelled()
    {
        $container = new Container;
        Container::setInstance($container);

        $dispatcher = m::mock(Dispatcher::class);
        $container->instance(Dispatcher::class, $dispatcher);

        $job = new DispatchBatchInChainTestJob;
        $nextJob = new DispatchBatchInChainTestJob;
        $job->chained = [serialize($nextJob)];
        $job->chainConnection = null;
        $job->chainQueue = null;
        $job->chainCatchCallbacks = [];

        $finallyCallback = null;

        $batch = m::mock(Batch::class);
        $pendingBatch = m::mock(PendingBatch::class);

        $pendingBatch->shouldReceive('finally')->once()->withArgs(function ($callback) use (&$finallyCallback) {
            $finallyCallback = $callback;

            return true;
        });
        $pendingBatch->shouldReceive('dispatch')->once()->andReturn($batch);

        $job->dispatchBatchInChain($pendingBatch);

        // Simulate cancelled batch
        $cancelledBatch = m::mock(Batch::class);
        $cancelledBatch->shouldReceive('cancelled')->andReturn(true);
        $dispatcher->shouldNotReceive('dispatch');

        $finallyCallback($cancelledBatch);
    }

    public function test_dispatches_next_job_when_batch_is_not_cancelled()
    {
        $container = new Container;
        Container::setInstance($container);

        $dispatcher = m::mock(Dispatcher::class);
        $container->instance(Dispatcher::class, $dispatcher);

        $job = new DispatchBatchInChainTestJob;
        $nextJob = new DispatchBatchInChainTestJob;
        $job->chained = [serialize($nextJob)];
        $job->chainConnection = 'redis';
        $job->chainQueue = 'high';
        $job->chainCatchCallbacks = [];

        $finallyCallback = null;

        $batch = m::mock(Batch::class);
        $pendingBatch = m::mock(PendingBatch::class);

        $pendingBatch->shouldReceive('finally')->once()->withArgs(function ($callback) use (&$finallyCallback) {
            $finallyCallback = $callback;

            return true;
        });
        $pendingBatch->shouldReceive('dispatch')->once()->andReturn($batch);

        $job->dispatchBatchInChain($pendingBatch);

        // Simulate non-cancelled batch
        $completedBatch = m::mock(Batch::class);
        $completedBatch->shouldReceive('cancelled')->andReturn(false);
        $dispatcher->shouldReceive('dispatch')->once()->withArgs(function ($next) {
            return $next instanceof DispatchBatchInChainTestJob
                && $next->connection === 'redis'
                && $next->queue === 'high'
                && $next->chainConnection === 'redis'
                && $next->chainQueue === 'high';
        });

        $finallyCallback($completedBatch);
    }

    public function test_propagates_catch_callbacks_to_batch()
    {
        $container = new Container;
        Container::setInstance($container);

        $catchCalled = false;

        $job = new DispatchBatchInChainTestJob;
        $job->chained = [];
        $job->chainCatchCallbacks = [function ($e) use (&$catchCalled) {
            $catchCalled = true;
        }];

        $catchCallback = null;

        $batch = m::mock(Batch::class);
        $pendingBatch = m::mock(PendingBatch::class);

        $pendingBatch->shouldReceive('catch')->once()->withArgs(function ($callback) use (&$catchCallback) {
            $catchCallback = $callback;

            return true;
        });
        $pendingBatch->shouldReceive('finally')->never();
        $pendingBatch->shouldReceive('dispatch')->once()->andReturn($batch);

        $job->dispatchBatchInChain($pendingBatch);

        // Simulate batch failure (no allowFailures)
        $failedBatch = m::mock(Batch::class);
        $failedBatch->shouldReceive('allowsFailures')->andReturn(false);

        $catchCallback($failedBatch, new \RuntimeException('test'));

        $this->assertTrue($catchCalled);
    }

    public function test_catch_callback_not_called_when_batch_allows_failures()
    {
        $container = new Container;
        Container::setInstance($container);

        $catchCalled = false;

        $job = new DispatchBatchInChainTestJob;
        $job->chained = [];
        $job->chainCatchCallbacks = [function ($e) use (&$catchCalled) {
            $catchCalled = true;
        }];

        $catchCallback = null;

        $batch = m::mock(Batch::class);
        $pendingBatch = m::mock(PendingBatch::class);

        $pendingBatch->shouldReceive('catch')->once()->withArgs(function ($callback) use (&$catchCallback) {
            $catchCallback = $callback;

            return true;
        });
        $pendingBatch->shouldReceive('dispatch')->once()->andReturn($batch);

        $job->dispatchBatchInChain($pendingBatch);

        // Simulate batch failure with allowFailures
        $failedBatch = m::mock(Batch::class);
        $failedBatch->shouldReceive('allowsFailures')->andReturn(true);

        $catchCallback($failedBatch, new \RuntimeException('test'));

        $this->assertFalse($catchCalled);
    }

    public function test_works_with_empty_chain()
    {
        $container = new Container;
        Container::setInstance($container);

        $job = new DispatchBatchInChainTestJob;
        $job->chained = [];
        $job->chainCatchCallbacks = [];

        $batch = m::mock(Batch::class);
        $pendingBatch = m::mock(PendingBatch::class);

        $pendingBatch->shouldReceive('catch')->never();
        $pendingBatch->shouldReceive('finally')->never();
        $pendingBatch->shouldReceive('dispatch')->once()->andReturn($batch);

        $result = $job->dispatchBatchInChain($pendingBatch);

        $this->assertSame($batch, $result);
    }

    public function test_propagates_chain_metadata_to_next_job()
    {
        $container = new Container;
        Container::setInstance($container);

        $dispatcher = m::mock(Dispatcher::class);
        $container->instance(Dispatcher::class, $dispatcher);

        $catchCallback = function () {
        };

        $job = new DispatchBatchInChainTestJob;
        $thirdJob = new DispatchBatchInChainTestJob;
        $secondJob = new DispatchBatchInChainTestJob;
        $job->chained = [serialize($secondJob), serialize($thirdJob)];
        $job->chainConnection = 'sqs';
        $job->chainQueue = 'processing';
        $job->chainCatchCallbacks = [$catchCallback];

        $finallyCallback = null;

        $batch = m::mock(Batch::class);
        $pendingBatch = m::mock(PendingBatch::class);

        $pendingBatch->shouldReceive('catch')->once();
        $pendingBatch->shouldReceive('finally')->once()->withArgs(function ($callback) use (&$finallyCallback) {
            $finallyCallback = $callback;

            return true;
        });
        $pendingBatch->shouldReceive('dispatch')->once()->andReturn($batch);

        $job->dispatchBatchInChain($pendingBatch);

        $completedBatch = m::mock(Batch::class);
        $completedBatch->shouldReceive('cancelled')->andReturn(false);

        $dispatcher->shouldReceive('dispatch')->once()->withArgs(function ($next) use ($catchCallback) {
            return $next instanceof DispatchBatchInChainTestJob
                && $next->connection === 'sqs'
                && $next->queue === 'processing'
                && $next->chainConnection === 'sqs'
                && $next->chainQueue === 'processing'
                && $next->chainCatchCallbacks === [$catchCallback]
                && count($next->chained) === 1;
        });

        $finallyCallback($completedBatch);
    }

    public function test_works_from_call_queued_closure()
    {
        $container = new Container;
        Container::setInstance($container);

        $dispatcher = m::mock(Dispatcher::class);
        $container->instance(Dispatcher::class, $dispatcher);

        $closureJob = CallQueuedClosure::create(function () {
        });
        $nextJob = new DispatchBatchInChainTestJob;
        $closureJob->chained = [serialize($nextJob)];
        $closureJob->chainConnection = null;
        $closureJob->chainQueue = null;
        $closureJob->chainCatchCallbacks = [];

        $batch = m::mock(Batch::class);
        $pendingBatch = m::mock(PendingBatch::class);

        $pendingBatch->shouldReceive('finally')->once();
        $pendingBatch->shouldReceive('dispatch')->once()->andReturn($batch);

        $result = $closureJob->dispatchBatchInChain($pendingBatch);

        $this->assertSame($batch, $result);
        $this->assertEmpty($closureJob->chained);
    }
}

class DispatchBatchInChainTestJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        //
    }
}
