<?php

namespace Illuminate\Tests\Integration\Console\Scheduling\Sequences;

use Illuminate\Console\Scheduling\Sequences\DueSequenceRunner;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledOccurrence;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduleOutbox;
use Illuminate\Console\Scheduling\Sequences\OutboxPublisher;
use Illuminate\Console\Scheduling\Sequences\SequenceManager;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class ConcurrencyTest extends SequenceTestCase
{
    protected function setUp(): void
    {
        if (getenv('SEQUENCE_MYSQL_DATABASE') === false) {
            $this->markTestSkipped('Set SEQUENCE_MYSQL_DATABASE to a disposable MariaDB/MySQL database.');
        }

        parent::setUp();
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);
        $app['config']->set('database.connections.sequence_test', [
            'driver' => 'mysql',
            'host' => getenv('SEQUENCE_MYSQL_HOST') ?: '127.0.0.1',
            'port' => getenv('SEQUENCE_MYSQL_PORT') ?: 3306,
            'database' => getenv('SEQUENCE_MYSQL_DATABASE'),
            'username' => getenv('SEQUENCE_MYSQL_USERNAME') ?: 'root',
            'password' => getenv('SEQUENCE_MYSQL_PASSWORD') ?: '',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
        ]);
        $app['config']->set('database.default', 'sequence_test');
        $app['config']->set('scheduling.definitions.process-v1', ProcessSequence::class);
    }

    protected function defineDatabaseMigrations()
    {
        $this->dropTestTables();
        parent::defineDatabaseMigrations();
        Schema::create('sequence_effects', function ($table) {
            $table->string('identity')->primary();
        });
        $this->beforeApplicationDestroyed(fn () => $this->dropTestTables());
    }

    private function dropTestTables(): void
    {
        (require __DIR__.'/../../../../../src/Illuminate/Console/Scheduling/Sequences/Console/stubs/sequences.stub')->down();
        Schema::dropIfExists('sequence_effects');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('sequence_entities');
    }

    private function createSequence(): int
    {
        return app(SequenceManager::class)->start(
            'process-v1', SequenceEntity::query()->create(['name' => 'Example']), now('UTC')->subSecond()
        )->id;
    }

    public function test_runners_claim_one_occurrence(): void
    {
        $id = $this->createSequence();
        $this->race('app(\Illuminate\Console\Scheduling\Sequences\DueSequenceRunner::class)->materialize('.$id.');');
        $this->assertDatabaseCount('scheduled_occurrences', 1);
        $this->assertDatabaseCount('schedule_outbox', 1);
    }

    public function test_publishers_claim_one_entry(): void
    {
        app(DueSequenceRunner::class)->materialize($this->createSequence());
        $this->race('app(\Illuminate\Console\Scheduling\Sequences\OutboxPublisher::class)->publish();');
        $this->assertDatabaseCount('jobs', 1);
        $this->assertNotNull(ScheduleOutbox::query()->sole()->published_at);
    }

    public function test_workers_execute_one_effect(): void
    {
        app(DueSequenceRunner::class)->materialize($this->createSequence());
        $id = ScheduledOccurrence::query()->sole()->id;
        $this->race('app(\Illuminate\Console\Scheduling\Sequences\OccurrenceExecutor::class)->execute('.$id.');');
        $this->assertDatabaseCount('sequence_effects', 1);
        $this->assertSame('succeeded', ScheduledOccurrence::query()->sole()->status);
        $this->assertSame(1, ScheduledOccurrence::query()->sole()->attempts);
    }

    public function test_process_dying_after_commit_recovers_the_same_identity(): void
    {
        $id = $this->createSequence();
        $process = $this->child('app(\Illuminate\Console\Scheduling\Sequences\DueSequenceRunner::class)->materialize('.$id.'); exit(71);');
        $process->run();
        $this->assertSame(71, $process->getExitCode(), $process->getErrorOutput());
        $this->assertDatabaseCount('jobs', 0);
        $identity = ScheduledOccurrence::query()->sole()->idempotencyKey();
        app(OutboxPublisher::class)->publish();
        $this->workQueue();
        $this->assertDatabaseHas('sequence_effects', ['identity' => $identity]);
        $this->assertSame('succeeded', ScheduledOccurrence::query()->sole()->status);
    }

    public function test_process_dying_after_broker_acceptance_republishes_without_a_second_effect(): void
    {
        app(DueSequenceRunner::class)->materialize($this->createSequence());
        $code = '\Illuminate\Support\Facades\Event::listen(\Illuminate\Queue\Events\JobQueued::class, function () { exit(72); }); '
            .'app(\Illuminate\Console\Scheduling\Sequences\OutboxPublisher::class)->publish();';
        $process = $this->child($code);
        $process->run();
        $this->assertSame(72, $process->getExitCode(), $process->getErrorOutput());
        $this->assertDatabaseCount('jobs', 1);
        $this->assertNull(ScheduleOutbox::query()->sole()->published_at);
        ScheduleOutbox::query()->update(['lease_until' => now('UTC')->subSecond()]);
        app(OutboxPublisher::class)->publish();
        $this->assertDatabaseCount('jobs', 2);
        $this->workQueue();
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('sequence_effects', 1);
        $this->assertSame('succeeded', ScheduledOccurrence::query()->sole()->status);
    }

    private function workQueue(): void
    {
        $this->artisan('queue:work', [
            'connection' => 'database', '--queue' => 'scheduled-actions',
            '--stop-when-empty' => true, '--sleep' => 0,
        ])->assertSuccessful();
    }

    private function child(string $action, ?string $gate = null): Process
    {
        $configuration = base64_encode(serialize([
            'database.default' => 'sequence_test',
            'database.connections.sequence_test' => config('database.connections.sequence_test'),
            'queue.connections.database' => config('queue.connections.database'),
            'scheduling' => config('scheduling'),
        ]));
        $code = 'require "vendor/autoload.php"; '
            .'$app = \Orchestra\Testbench\Foundation\Application::create(); '
            .'$app["config"]->set(unserialize(base64_decode($argv[1]))); '
            .'$app["db"]->purge(); ';
        if ($gate !== null) {
            $code .= 'echo "READY\n"; flush(); $deadline = microtime(true) + 20; '
                .'while (file_get_contents($argv[2]) !== "go") { '
                .'if (microtime(true) > $deadline) { exit(73); } usleep(10000); } ';
        }

        return new Process([PHP_BINARY, '-r', $code.$action, $configuration, $gate ?? ''], dirname(__DIR__, 5), timeout: 30);
    }

    private function race(string $action): void
    {
        $gate = tempnam(sys_get_temp_dir(), 'sequence-race-');
        $workers = [$this->child($action, $gate), $this->child($action, $gate)];
        try {
            foreach ($workers as $worker) {
                $worker->start();
            }
            $deadline = microtime(true) + 20;
            do {
                $ready = count(array_filter($workers, fn ($worker) => str_contains($worker->getOutput(), 'READY')));
                if ($ready === 2) {
                    break;
                }
                foreach ($workers as $worker) {
                    if (! $worker->isRunning()) {
                        $this->fail($worker->getErrorOutput().$worker->getOutput());
                    }
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            $this->assertSame(2, $ready);
            file_put_contents($gate, 'go');
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertSame(0, $worker->getExitCode(), $worker->getErrorOutput().$worker->getOutput());
            }
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
            unlink($gate);
        }
    }
}
