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
use Orchestra\Testbench\TestCase;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Throwable;

use function Orchestra\Testbench\remote;

#[WithMigration]
#[WithMigration('queue')]
class BatchableTransactionTest extends DatabaseTestCase
{
    use DatabaseMigrations;

    protected function defineEnvironment($app)
    {
        $app['config']->set([
            'queue.default' => 'database',
        ]);
    }

    public function testItCanHandleTimeoutJob()
    {
        Bus::batch([new TimeOutJobWithTransaction()])
            ->allowFailures()
            ->dispatch();

        sleep(2);

        try {
            $process = remote('queue:work')->setTimeout(2);
            $process->run();
        } catch (Throwable $e) {
            $this->assertInstanceOf(ProcessTimedOutException::class, $e);
        }

        dd(
            $process->getOutput(),
            DB::table('jobs')->get(),
            DB::table('failed_jobs')->get(),
            DB::table('job_batches')->get()
        );
    }
}

class TimeOutJobWithTransaction implements ShouldQueue
{
    use InteractsWithQueue, Queueable, Batchable;

    public int $tries = 1;
    public int $timeout = 2;

    public function handle(): void
    {
        DB::transaction(fn() => sleep(20));
    }
}
