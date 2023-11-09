<?php

namespace Illuminate\Tests\Integration\Database\Queue;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\WithMigration;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Throwable;

use function Orchestra\Testbench\remote;

#[WithMigration]
#[WithMigration('queue')]
class BatchableTransactionTest extends DatabaseTestCase
{
    use DatabaseMigrations;

    public function testItCanHandleTimeoutJob()
    {
        Bus::batch([new TimeOutJobWithTransaction()])
            ->allowFailures()
            ->dispatch();

        try {
            $process = remote('queue:work --once --stop-when-empty --ansi');
            $process->setTimeout(2)->start();
        } catch (Throwable $e) {
            $this->assertInstanceOf(ProcessTimedOutException::class, $e);
        }

        dd(
            $process->getOutput(),
            DB::table('failed_jobs')->get(),
            DB::table('job_batches')->get()
        );
    }
}

class TimeOutJobWithTransaction implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Batchable;

    public int $tries = 1;
    public int $timeout = 5;

    public function handle(): void
    {
        DB::transaction(fn () => sleep(10));
    }
}
