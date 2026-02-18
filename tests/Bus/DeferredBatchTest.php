<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\BatchFactory;
use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Bus\DeferredBatch;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\PendingBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Facade;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class DeferredBatchTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $container = new Container;
        Container::setInstance($container);
        Facade::setFacadeApplication($container);

        $queue = m::mock(Factory::class);
        $container->instance(Factory::class, $queue);
        $container->alias(Factory::class, 'queue');

        $dispatcher = new Dispatcher($container, function () use ($queue) {
            return $queue;
        });

        $container->instance(BusDispatcher::class, $dispatcher);
        $container->alias(BusDispatcher::class, 'bus');

        $events = m::mock(EventDispatcher::class);
        $events->shouldReceive('dispatch')->byDefault();
        $container->instance(EventDispatcher::class, $events);

        $repository = new DatabaseBatchRepository(new BatchFactory($queue), DB::connection(), 'job_batches');
        $container->instance(BatchRepository::class, $repository);

        $this->createSchema();
    }

    public function createSchema()
    {
        DB::connection()->getSchemaBuilder()->create('job_batches', function ($table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->text('failed_job_ids');
            $table->text('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        DB::connection()->getSchemaBuilder()->drop('job_batches');

        m::close();

        parent::tearDown();
    }

    public function test_deferred_batch_accepts_closure_builder()
    {
        $deferred = new DeferredBatch(function () {
            return null;
        });

        $this->assertNotNull($deferred->builder);
    }

    public function test_deferred_batch_accepts_invokable_class_builder()
    {
        $builder = new DeferredBatchTestInvokableBuilder;
        $deferred = new DeferredBatch($builder);

        $this->assertNotNull($deferred->builder);
    }

    public function test_deferred_batch_returns_null_continues_chain()
    {
        $deferred = new DeferredBatch(function () {
            return null;
        });

        // Should not throw - returns early when builder returns null
        $deferred->handle();

        $this->assertTrue(true);
    }

    public function test_deferred_batch_throws_on_invalid_return()
    {
        $deferred = new DeferredBatch(function () {
            return 'not a pending batch';
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DeferredBatch builder must return a PendingBatch or null.');

        $deferred->handle();
    }

    public function test_deferred_batch_dispatches_batch_from_builder()
    {
        $container = Container::getInstance();

        $queue = $container->make(Factory::class);
        $queue->shouldReceive('connection')->andReturn(
            $connection = m::mock(stdClass::class)
        );
        $connection->shouldReceive('bulk')->once();

        $job = new DeferredBatchTestJob;

        $deferred = new DeferredBatch(function () use ($container, $job) {
            return new PendingBatch($container, collect([$job]));
        });

        $deferred->handle();

        $this->assertNotNull($job->batchId);
    }

    public function test_deferred_batch_survives_serialization_with_closure()
    {
        $deferred = new DeferredBatch(function () {
            return null;
        });

        $unserialized = unserialize(serialize($deferred));

        $this->assertNotNull($unserialized->builder);

        // Should still work after deserialization
        $unserialized->handle();

        $this->assertTrue(true);
    }

    public function test_deferred_batch_survives_serialization_with_invokable()
    {
        $builder = new DeferredBatchTestInvokableBuilder;
        $deferred = new DeferredBatch($builder);

        $unserialized = unserialize(serialize($deferred));

        $this->assertNotNull($unserialized->builder);
    }

    public function test_deferred_batch_null_return_preserves_chain_for_worker()
    {
        $nextJob = new DeferredBatchTestJob;

        $deferred = new DeferredBatch(function () {
            return null;
        });

        $deferred->chainConnection = 'redis';
        $deferred->chainQueue = 'high';
        $deferred->chained = [serialize($nextJob)];

        // Builder returns null → handle() returns early
        // Chain remains intact for the queue worker to call dispatchNextJobInChain()
        $deferred->handle();

        $this->assertNotEmpty($deferred->chained);
    }

    public function test_deferred_batch_null_return_dispatch_next_propagates_chain_settings()
    {
        $dispatchedNext = null;

        $dispatcher = m::mock(BusDispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->andReturnUsing(function ($job) use (&$dispatchedNext) {
            $dispatchedNext = $job;
        });

        $container = Container::getInstance();
        $container->instance(BusDispatcher::class, $dispatcher);

        $nextJob = new DeferredBatchTestJob;

        $deferred = new DeferredBatch(function () {
            return null;
        });

        $deferred->chainConnection = 'redis';
        $deferred->chainQueue = 'high';
        $deferred->chained = [serialize($nextJob)];

        // Simulate what the queue worker does after handle() succeeds
        $deferred->dispatchNextJobInChain();

        $this->assertNotNull($dispatchedNext);
        $this->assertEquals('redis', $dispatchedNext->connection);
        $this->assertEquals('high', $dispatchedNext->queue);
        $this->assertEquals('redis', $dispatchedNext->chainConnection);
        $this->assertEquals('high', $dispatchedNext->chainQueue);
    }

    public function test_deferred_batch_attaches_chain_remainder_to_batch()
    {
        $container = Container::getInstance();

        $queue = $container->make(Factory::class);
        $queue->shouldReceive('connection')->andReturn(
            $connection = m::mock(stdClass::class)
        );
        $connection->shouldReceive('bulk')->once();

        $batchJob = new DeferredBatchTestJob;
        $nextJob = new DeferredBatchTestJob;

        $deferred = new DeferredBatch(function () use ($container, $batchJob) {
            return new PendingBatch($container, collect([$batchJob]));
        });

        $deferred->chainConnection = 'redis';
        $deferred->chainQueue = 'high';
        $deferred->chained = [serialize($nextJob)];

        $deferred->handle();

        // Chain should have been cleared (moved to batch finally callback)
        $this->assertEmpty($deferred->chained);
    }

    public function test_deferred_batch_propagates_chain_catch_callbacks()
    {
        $container = Container::getInstance();

        $queue = $container->make(Factory::class);
        $queue->shouldReceive('connection')->andReturn(
            $connection = m::mock(stdClass::class)
        );
        $connection->shouldReceive('bulk')->once();

        $catchCalled = false;

        $batchJob = new DeferredBatchTestJob;

        $deferred = new DeferredBatch(function () use ($container, $batchJob) {
            return new PendingBatch($container, collect([$batchJob]));
        });

        $deferred->chainCatchCallbacks = [function ($e) use (&$catchCalled) {
            $catchCalled = true;
        }];

        $deferred->handle();

        // The catch callback should have been registered on the batch
        // We verify this didn't throw - the actual catch invocation
        // happens when the batch fails at runtime
        $this->assertFalse($catchCalled);
    }

    public function test_dispatcher_deferred_batch_helper()
    {
        $container = Container::getInstance();

        $dispatcher = new Dispatcher($container);

        $deferred = $dispatcher->deferredBatch(function () {
            return null;
        });

        $this->assertInstanceOf(DeferredBatch::class, $deferred);
    }

    public function test_deferred_batch_implements_should_queue()
    {
        $deferred = new DeferredBatch(function () {
            return null;
        });

        $this->assertInstanceOf(ShouldQueue::class, $deferred);
    }

    public function test_deferred_batch_with_queue_and_connection_settings()
    {
        $deferred = new DeferredBatch(function () {
            return null;
        });

        $deferred->onQueue('high');
        $deferred->onConnection('redis');

        $this->assertEquals('high', $deferred->queue);
        $this->assertEquals('redis', $deferred->connection);
    }
}

class DeferredBatchTestJob implements ShouldQueue
{
    use Batchable, Dispatchable, Queueable;

    public function handle()
    {
        //
    }
}

class DeferredBatchTestInvokableBuilder
{
    public function __invoke()
    {
        return null;
    }
}
