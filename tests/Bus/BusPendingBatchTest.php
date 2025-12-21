<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class BusPendingBatchTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_pending_batch_may_be_configured_and_dispatched()
    {
        $container = new Container;

        $eventDispatcher = m::mock(Dispatcher::class);
        $eventDispatcher->shouldReceive('dispatch')->once();

        $container->instance(Dispatcher::class, $eventDispatcher);

        $job = new class
        {
            use Batchable;
        };

        $pendingBatch = new PendingBatch($container, new Collection([$job]));

        $pendingBatch = $pendingBatch->before(function () {
            //
        })->progress(function () {
            //
        })->then(function () {
            //
        })->catch(function () {
            //
        })->allowFailures()->onConnection('test-connection')->onQueue('test-queue')->withOption('extra-option', 123);

        $this->assertSame('test-connection', $pendingBatch->connection());
        $this->assertSame('test-queue', $pendingBatch->queue());
        $this->assertCount(1, $pendingBatch->beforeCallbacks());
        $this->assertCount(1, $pendingBatch->progressCallbacks());
        $this->assertCount(1, $pendingBatch->thenCallbacks());
        $this->assertCount(1, $pendingBatch->catchCallbacks());
        $this->assertArrayHasKey('extra-option', $pendingBatch->options);
        $this->assertSame(123, $pendingBatch->options['extra-option']);

        $repository = m::mock(BatchRepository::class);
        $repository->shouldReceive('store')->once()->with($pendingBatch)->andReturn($batch = m::mock(stdClass::class));
        $batch->shouldReceive('add')->once()->with(m::type(Collection::class))->andReturn($batch = m::mock(Batch::class));

        $container->instance(BatchRepository::class, $repository);

        $pendingBatch->dispatch();
    }

    public function test_batch_is_deleted_from_storage_if_exception_thrown_during_batching()
    {
        $this->expectException(RuntimeException::class);

        $container = new Container;

        $job = new class {
        };

        $pendingBatch = new PendingBatch($container, new Collection([$job]));

        $repository = m::mock(BatchRepository::class);

        $repository->shouldReceive('store')->once()->with($pendingBatch)->andReturn($batch = m::mock(stdClass::class));

        $batch->id = 'test-id';

        $batch->shouldReceive('add')->once()->andReturnUsing(function () {
            throw new RuntimeException('Failed to add jobs...');
        });

        $repository->shouldReceive('delete')->once()->with('test-id');

        $container->instance(BatchRepository::class, $repository);

        $pendingBatch->dispatch();
    }

    public function test_batch_is_dispatched_when_dispatchif_is_true()
    {
        $container = new Container;

        $eventDispatcher = m::mock(Dispatcher::class);
        $eventDispatcher->shouldReceive('dispatch')->once();
        $container->instance(Dispatcher::class, $eventDispatcher);

        $job = new class
        {
            use Batchable;
        };

        $pendingBatch = new PendingBatch($container, new Collection([$job]));

        $repository = m::mock(BatchRepository::class);
        $repository->shouldReceive('store')->once()->andReturn($batch = m::mock(stdClass::class));
        $batch->shouldReceive('add')->once()->andReturn($batch = m::mock(Batch::class));

        $container->instance(BatchRepository::class, $repository);

        $result = $pendingBatch->dispatchIf(true);

        $this->assertInstanceOf(Batch::class, $result);
    }

    public function test_batch_is_not_dispatched_when_dispatchif_is_false()
    {
        $container = new Container;

        $eventDispatcher = m::mock(Dispatcher::class);
        $eventDispatcher->shouldNotReceive('dispatch');
        $container->instance(Dispatcher::class, $eventDispatcher);

        $job = new class
        {
            use Batchable;
        };

        $pendingBatch = new PendingBatch($container, new Collection([$job]));

        $repository = m::mock(BatchRepository::class);
        $container->instance(BatchRepository::class, $repository);

        $result = $pendingBatch->dispatchIf(false);

        $this->assertNull($result);
    }

    public function test_batch_is_dispatched_when_dispatchunless_is_false()
    {
        $container = new Container;

        $eventDispatcher = m::mock(Dispatcher::class);
        $eventDispatcher->shouldReceive('dispatch')->once();
        $container->instance(Dispatcher::class, $eventDispatcher);

        $job = new class
        {
            use Batchable;
        };

        $pendingBatch = new PendingBatch($container, new Collection([$job]));

        $repository = m::mock(BatchRepository::class);
        $repository->shouldReceive('store')->once()->andReturn($batch = m::mock(stdClass::class));
        $batch->shouldReceive('add')->once()->andReturn($batch = m::mock(Batch::class));

        $container->instance(BatchRepository::class, $repository);

        $result = $pendingBatch->dispatchUnless(false);

        $this->assertInstanceOf(Batch::class, $result);
    }

    public function test_batch_is_not_dispatched_when_dispatchunless_is_true()
    {
        $container = new Container;

        $eventDispatcher = m::mock(Dispatcher::class);
        $eventDispatcher->shouldNotReceive('dispatch');
        $container->instance(Dispatcher::class, $eventDispatcher);

        $job = new class
        {
            use Batchable;
        };

        $pendingBatch = new PendingBatch($container, new Collection([$job]));

        $repository = m::mock(BatchRepository::class);
        $container->instance(BatchRepository::class, $repository);

        $result = $pendingBatch->dispatchUnless(true);

        $this->assertNull($result);
    }

    public function test_batch_before_event_is_called()
    {
        $container = new Container;

        $eventDispatcher = m::mock(Dispatcher::class);
        $eventDispatcher->shouldReceive('dispatch')->once();

        $container->instance(Dispatcher::class, $eventDispatcher);

        $job = new class
        {
            use Batchable;
        };

        $beforeCalled = false;

        $pendingBatch = new PendingBatch($container, new Collection([$job]));

        $pendingBatch = $pendingBatch->before(function () use (&$beforeCalled) {
            $beforeCalled = true;
        })->onConnection('test-connection')->onQueue('test-queue');

        $repository = m::mock(BatchRepository::class);
        $repository->shouldReceive('store')->once()->with($pendingBatch)->andReturn($batch = m::mock(stdClass::class));
        $batch->shouldReceive('add')->once()->with(m::type(Collection::class))->andReturn($batch = m::mock(Batch::class));

        $container->instance(BatchRepository::class, $repository);

        $pendingBatch->dispatch();

        $this->assertTrue($beforeCalled);
    }

    public function test_it_throws_exception_if_batched_job_is_not_batchable(): void
    {
        $nonBatchableJob = new class {
        };

        $this->expectException(RuntimeException::class);

        new PendingBatch(new Container, new Collection([$nonBatchableJob]));
    }

    public function test_it_throws_an_exception_if_batched_job_contains_batch_with_nonbatchable_job(): void
    {
        $this->expectException(RuntimeException::class);

        $container = new Container;
        new PendingBatch(
            $container,
            new Collection(
                [new PendingBatch($container, new Collection([new BatchableJob, new class {
                }]))]
            )
        );
    }

    public function test_it_can_batch_a_closure(): void
    {
        new PendingBatch(
            new Container,
            new Collection([
                function () {
                },
            ])
        );
        $this->expectNotToPerformAssertions();
    }

    public function test_allow_failures_with_boolean_true_enables_failure_tolerance(): void
    {
        $batch = new PendingBatch(new Container, new Collection([new BatchableJob]));

        $result = $batch->allowFailures(true);

        $this->assertSame($batch, $result);
        $this->assertTrue($batch->options['allowFailures']);
        $this->assertEmpty($batch->failureCallbacks());
    }

    public function test_allow_failures_with_boolean_false_disables_failure_tolerance(): void
    {
        $batch = new PendingBatch(new Container, new Collection([new BatchableJob]));

        $result = $batch->allowFailures(false);

        $this->assertSame($batch, $result);
        $this->assertFalse($batch->options['allowFailures']);
        $this->assertEmpty($batch->failureCallbacks());
    }

    public function test_allow_failures_with_single_closure_registers_callback(): void
    {
        $batch = new PendingBatch(new Container, new Collection([new BatchableJob]));

        $result = $batch->allowFailures(static fn (): true => true);

        $this->assertSame($batch, $result);
        $this->assertTrue($batch->options['allowFailures']);
        $this->assertCount(1, $batch->failureCallbacks());
    }

    public function test_allow_failures_with_single_callable_registers_callback(): void
    {
        $batch = new PendingBatch(new Container, new Collection([new BatchableJob]));

        $result = $batch->allowFailures('strlen');

        $this->assertSame($batch, $result);
        $this->assertTrue($batch->options['allowFailures']);
        $this->assertCount(1, $batch->failureCallbacks());
    }

    public function test_allow_failures_with_array_of_callables_registers_multiple_callbacks(): void
    {
        $batch = new PendingBatch(new Container, new Collection([new BatchableJob]));

        $result = $batch->allowFailures([
            static fn (): true => true,
            'strlen',
            [$batch, 'failureCallbacks'],
            strlen(...),
        ]);

        $this->assertSame($batch, $result);
        $this->assertTrue($batch->options['allowFailures']);
        $this->assertCount(4, $batch->failureCallbacks());
    }

    public function test_allow_failures_registers_only_valid_callbacks(): void
    {
        $batch = new PendingBatch(new Container, new Collection([new BatchableJob]));

        $result = $batch->allowFailures([
            // 3 valid
            static fn (): true => true,
            'strlen',
            [$batch, 'failureCallbacks'],
            // 5 invalid
            'invalid_function_name',
            123,
            null,
            [],
            new stdClass,
        ]);

        $this->assertSame($batch, $result);
        $this->assertTrue($batch->options['allowFailures']);
        $this->assertCount(3, $batch->failureCallbacks());
    }

    public function test_allow_failures_with_empty_array_enables_tolerance_without_callbacks(): void
    {
        $batch = new PendingBatch(new Container, new Collection([new BatchableJob]));

        $result = $batch->allowFailures([]);

        $this->assertSame($batch, $result);
        $this->assertTrue($batch->options['allowFailures']);
        $this->assertEmpty($batch->failureCallbacks());
    }

    public function test_allow_failures_is_chainable(): void
    {
        $batch = new PendingBatch(new Container, new Collection([new BatchableJob]));

        $this->assertSame($batch, $batch->allowFailures(true));
        $this->assertSame($batch, $batch->allowFailures(false));
        $this->assertSame($batch, $batch->allowFailures(static fn (): true => true));
        $this->assertSame($batch, $batch->allowFailures('strlen'));
        $this->assertSame($batch, $batch->allowFailures([static fn (): true => true, 'strlen']));
        $this->assertSame($batch, $batch->allowFailures([]));
    }

    public function test_failure_callbacks_accessor_returns_registered_callbacks(): void
    {
        $batch = new PendingBatch(new Container, new Collection([new BatchableJob]));

        $this->assertEmpty($batch->failureCallbacks());

        $batch->allowFailures(
            static fn (): true => true
        );

        $this->assertCount(1, $batch->failureCallbacks());

        $freshBatch = new PendingBatch(new Container, new Collection([new BatchableJob]));

        $freshBatch->allowFailures([
            'strlen',
            [$freshBatch, 'failureCallbacks'],
        ]);

        $this->assertCount(2, $freshBatch->failureCallbacks());

        $anotherBatch = new PendingBatch(new Container, new Collection([new BatchableJob]));

        $anotherBatch->allowFailures([
            static fn (): false => false,
            'trim',
            123,
            'invalid_function',
        ]);

        $this->assertCount(2, $anotherBatch->failureCallbacks());
    }
}

class BatchableJob
{
    use Batchable;
}
