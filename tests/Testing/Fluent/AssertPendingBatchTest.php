<?php

namespace Illuminate\Testing\Fluent;

use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Testing\Fakes\PendingBatchFake;
use Orchestra\Testbench\TestCase;

class AssertPendingBatchTest extends TestCase
{
    public function test_pending_batch_has()
    {
        Bus::fake();

        Bus::batch([
            new AJob(1, 2),
            new BJob,
            new CJob,
            new DJob,
        ])->dispatch();

        Bus::assertBatched(fn (PendingBatchFake $assert) =>
            $assert->has(AJob::class, [1, 2])
                ->has(BJob::class)
                ->has(CJob::class)
                ->etc()
        );
    }

    public function test_pending_batch_missing()
    {
        Bus::fake();

        Bus::batch([
            new AJob,
            new CJob,
        ])->dispatch();

        Bus::assertBatched(fn (PendingBatchFake $assert) =>
            $assert->has(AJob::class)
                ->missing(BJob::class)
        );
    }

    public function test_pending_batch_has_all_and_missing_all()
    {
        Bus::fake();

        Bus::batch([
            new AJob,
            new BJob,
        ])->dispatch();

        Bus::assertBatched(fn (PendingBatchFake $assert) =>
            $assert->hasAll([AJob::class, BJob::class])
                ->missingAll([CJob::class, DJob::class])
        );
    }

    public function test_pending_batch_has_and_has_any()
    {
        Bus::fake();

        Bus::batch([
            new AJob,
            new CJob,
        ])->dispatch();

        Bus::assertBatched(fn (PendingBatchFake $assert) =>
            $assert->has(2)
                ->has(AJob::class)
                ->hasAny(BJob::class, CJob::class, DJob::class)
        );
    }

    public function test_nested_jobs_in_pending_batch()
    {
        Bus::fake();

        Bus::batch([
            [
                new AJob(1),
                new BJob(1),
            ],
            new CJob(2),
            [
                new CJob(2),
                new DJob(2),
            ],
        ])->dispatch();

        Bus::assertBatched(fn (PendingBatchFake $assert) =>
            $assert->has(3)
                ->first(fn (PendingBatchFake $assert) =>
                    $assert->has(AJob::class, [1])
                        ->has(BJob::class, [1])
                )
                ->nth(1, fn (PendingBatchFake $assert) =>
                    $assert->has(CJob::class, [2])
                )
                ->nth(2, fn (PendingBatchFake $assert) =>
                    $assert->has(CJob::class, [2])
                        ->has(DJob::class, [2])
                )
        );
    }

    public function test_nth_job_in_pending_batch()
    {
        Bus::fake();

        Bus::batch([
            new AJob(0, 1),
            new BJob(1),
            new CJob(1),
        ])->dispatch();

        Bus::assertBatched(fn (PendingBatchFake $assert) =>
            $assert->nth(0, AJob::class, [0, 1])
                ->nth(1, BJob::class, [1])
                ->nth(2, CJob::class, [1])
        );
    }

    public function test_equal_jobs_in_pending_batch()
    {
        Bus::fake();

        Bus::batch([
            new AJob(0, 1),
            new BJob(1),
            new CJob(1),
            new DJob(2),
            new EJob(2, 3),
        ])->dispatch();

        Bus::assertBatched(fn (PendingBatchFake $assert) =>
            $assert->equal([
                AJob::class => [0, 1],
                BJob::class => [1],
                CJob::class => [1],
                DJob::class => [2],
                EJob::class => [2, 3],
            ])
        );
    }

    public function test_etc_with_additional_job()
    {
        Bus::fake();

        Bus::batch([
            new AJob,
            new BJob,
            new CJob,
            new DJob,
        ])->dispatch();

        Bus::assertBatched(fn (PendingBatchFake $assert) =>
            $assert->has(AJob::class)
                ->has(BJob::class)
                ->has(CJob::class)
                ->etc()
        );
    }
}

trait Parameterable
{
    public $parameters = [];

    public function __construct(...$parameters) {
        $this->parameters = $parameters;
    }
}

class AJob {
    use Queueable, Batchable, Parameterable;
}

class BJob {
    use Queueable, Batchable, Parameterable;
}

class CJob {
    use Queueable, Batchable, Parameterable;
}

class DJob {
    use Queueable, Batchable, Parameterable;
}

class EJob {
    use Queueable, Batchable, Parameterable;
}