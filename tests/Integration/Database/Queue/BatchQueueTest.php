<?php

namespace Illuminate\Tests\Integration\Database\Queue;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration('laravel', 'queue')]
#[WithConfig('queue.default', 'database')]
class BatchQueueTest extends DatabaseTestCase
{
    public function testBatchedJobsArePushedToTheirOwnQueue()
    {
        Bus::batch([
            (new BatchQueueTestJob)->onQueue('high'),
            new BatchQueueTestJob,
            (new BatchQueueTestJob)->onQueue('low'),
        ])->dispatch();

        $this->assertSame(
            ['high', 'default', 'low'],
            DB::table('jobs')->orderBy('id')->pluck('queue')->all()
        );
    }

    public function testBatchQueueTakesPrecedenceOverJobQueues()
    {
        Bus::batch([
            (new BatchQueueTestJob)->onQueue('high'),
            new BatchQueueTestJob,
        ])->onQueue('batches')->dispatch();

        $this->assertSame(
            ['batches', 'batches'],
            DB::table('jobs')->orderBy('id')->pluck('queue')->all()
        );
    }
}

class BatchQueueTestJob implements ShouldQueue
{
    use Batchable, Queueable;
}
