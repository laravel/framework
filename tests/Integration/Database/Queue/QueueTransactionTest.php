<?php

namespace Illuminate\Tests\Integration\Database\Queue;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Throwable;

use function Orchestra\Testbench\remote;

#[RequiresPhpExtension('pcntl')]
#[WithMigration('laravel', 'queue')]
#[WithConfig('queue.default', 'database')]
class QueueTransactionTest extends DatabaseTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->usesSqliteInMemoryDatabaseConnection()) {
            $this->markTestSkipped('Test does not support using :memory: database connection');
        }
    }

    #[DataProvider('timeoutJobs')]
    public function testItCanHandleTimeoutJob($job)
    {
        dispatch($job);

        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());

        try {
            remote('queue:work --stop-when-empty', [
                'DB_CONNECTION' => config('database.default'),
                'QUEUE_CONNECTION' => config('queue.default'),
            ])->run();
        } catch (Throwable $e) {
            $this->assertInstanceOf(ProcessSignaledException::class, $e);
            $this->assertSame('The process has been signaled with signal "9".', $e->getMessage());
        }

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('failed_jobs')->count());
    }

    public static function timeoutJobs(): array
    {
        return [
            [new Fixtures\TimeOutJobWithTransaction()],
            [new Fixtures\TimeOutJobWithNestedTransactions()],
            [new Fixtures\TimeOutNonBatchableJobWithTransaction()],
            [new Fixtures\TimeOutNonBatchableJobWithNestedTransactions()],
        ];
    }
}
