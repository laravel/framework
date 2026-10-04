<?php

namespace Illuminate\Tests\Integration\Console\Scheduling\Sequences;

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Sequences\DueSequenceRunner;
use Illuminate\Console\Scheduling\Sequences\Jobs\ExecuteScheduledOccurrence;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledOccurrence;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduledSequence;
use Illuminate\Console\Scheduling\Sequences\Models\ScheduleOutbox;
use Illuminate\Console\Scheduling\Sequences\OccurrenceExecutor;
use Illuminate\Console\Scheduling\Sequences\OutboxPublisher;
use Illuminate\Console\Scheduling\Sequences\SequenceDefinition;
use Illuminate\Console\Scheduling\Sequences\SequenceManager;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;

class SchedulingTest extends SequenceTestCase
{
    private TestSequence $definition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-01-10 12:00:00', 'UTC'));
        $this->definition = new TestSequence;
        $this->app->instance(TestSequence::class, $this->definition);
        config(['scheduling.definitions.test-v1' => TestSequence::class]);
    }

    private function start(?CarbonImmutable $start = null): ScheduledSequence
    {
        return app(SequenceManager::class)->start('test-v1', SequenceEntity::query()->create(['name' => 'Example']), $start ?? CarbonImmutable::now('UTC'));
    }

    private function occurrence(): ScheduledOccurrence
    {
        $sequence = $this->start();
        app(DueSequenceRunner::class)->materialize($sequence->id);

        return $sequence->occurrences()->sole();
    }

    public function test_scheduler_registration_is_explicit_and_uses_an_ordinary_event(): void
    {
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
        $this->assertCount(0, $schedule->events());
        $event = $schedule->sequences(10)->everyMinute()->withoutOverlapping();
        $this->assertStringContainsString('sequences:run', $event->command);
        $this->assertStringContainsString('--limit=10', $event->command);
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    public function test_runner_command_recovers_committed_unpublished_work(): void
    {
        $occurrence = $this->occurrence();
        $this->assertDatabaseCount('jobs', 0);
        $this->artisan('sequences:run')->assertSuccessful();
        $this->assertDatabaseCount('jobs', 1);
        $this->assertNotNull($occurrence->outbox->published_at);
    }

    public function test_migration_generator_is_registered_and_rejects_duplicate_migrations(): void
    {
        $files = app('files');
        $directory = database_path('migrations');
        $files->ensureDirectoryExists($directory);

        try {
            $this->artisan('make:scheduled-sequences-table')->assertSuccessful();
            $paths = $files->glob($directory.'/*_create_scheduled_sequences_table.php');
            $this->assertCount(1, $paths);
            $this->assertStringContainsString("Schema::create('schedule_outbox'", $files->get($paths[0]));
            $this->artisan('make:scheduled-sequences-table')->assertFailed();
        } finally {
            foreach ($files->glob($directory.'/*_create_scheduled_sequences_table.php') as $path) {
                $files->delete($path);
            }
        }
    }

    public function test_future_state_is_queryable_without_queue_records(): void
    {
        $sequence = $this->start(CarbonImmutable::now('UTC')->addDay());
        $this->assertSame(1, ScheduledSequence::query()->forEntity($sequence->entity)->count());
        $this->assertSame(0, ScheduledSequence::query()->due()->count());
        $this->assertDatabaseCount('scheduled_occurrences', 0);
        $this->assertDatabaseCount('schedule_outbox', 0);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(['PT0S', 'P1D', 'P3D'], $sequence->offsets);
    }

    public function test_finite_catch_up_is_bounded_and_handoff_is_atomic(): void
    {
        $sequence = $this->start(CarbonImmutable::now('UTC')->subDays(10));
        $this->assertSame(2, app(DueSequenceRunner::class)->run(2));
        $this->assertSame('active', $sequence->fresh()->status);
        $this->assertSame(1, app(DueSequenceRunner::class)->run(2));
        $this->assertSame('completed', $sequence->fresh()->status);
        $this->assertNull($sequence->fresh()->next_at);
        $this->assertDatabaseCount('scheduled_occurrences', 3);
        $this->assertDatabaseCount('schedule_outbox', 3);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(0, app(DueSequenceRunner::class)->run());
    }

    public function test_failed_outbox_insert_rolls_back_occurrence_and_schedule(): void
    {
        $sequence = $this->start();
        $event = 'eloquent.creating: '.ScheduleOutbox::class;
        Event::listen($event, fn () => throw new RuntimeException('Injected storage failure'));
        try {
            app(DueSequenceRunner::class)->materialize($sequence->id);
            $this->fail('The injected failure should propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected storage failure', $exception->getMessage());
        } finally {
            Event::forget($event);
        }
        $this->assertDatabaseCount('scheduled_occurrences', 0);
        $this->assertDatabaseCount('schedule_outbox', 0);
        $this->assertSame(0, $sequence->fresh()->next_number);
        $this->assertTrue($sequence->fresh()->next_at->equalTo(now('UTC')));
    }

    public function test_outbox_survives_a_runner_stopping_before_publication(): void
    {
        Queue::fake();
        $occurrence = $this->occurrence();
        Queue::assertNothingPushed();
        $this->assertSame(1, app(OutboxPublisher::class)->publish());
        Queue::assertPushed(ExecuteScheduledOccurrence::class, fn ($job) => $job->occurrenceId === $occurrence->id);
        $this->assertNotNull($occurrence->outbox->fresh()->published_at);
        $this->assertSame(0, app(OutboxPublisher::class)->publish());
    }

    public function test_completed_scheduling_does_not_suppress_the_final_action_or_allow_duplicates(): void
    {
        $this->definition->steps = ['PT0S'];
        $occurrence = $this->occurrence();
        $this->assertSame('completed', $occurrence->sequence->status);
        $executor = app(OccurrenceExecutor::class);
        $this->assertTrue($executor->execute($occurrence->id));
        $this->assertTrue($executor->execute($occurrence->id));
        $this->assertSame(1, $this->definition->runs);
        $this->assertSame('succeeded', $occurrence->fresh()->status);
        $this->assertSame(1, $occurrence->fresh()->attempts);
    }

    public function test_cancelled_queued_work_is_skipped_and_cancel_is_idempotent(): void
    {
        $occurrence = $this->occurrence();
        $manager = app(SequenceManager::class);
        $cancelled = $manager->cancel($occurrence->sequence);
        $this->assertSame($cancelled->revision, $manager->cancel($cancelled)->revision);
        app(OccurrenceExecutor::class)->execute($occurrence->id);
        $this->assertSame('skipped', $occurrence->fresh()->status);
        $this->assertSame(0, $this->definition->runs);
    }

    public function test_rescheduling_invalidates_old_work_and_allocates_new_identity(): void
    {
        $old = $this->occurrence();
        $sequence = app(SequenceManager::class)->reschedule($old->sequence, CarbonImmutable::now('UTC'));
        app(DueSequenceRunner::class)->materialize($sequence->id);
        $new = $sequence->occurrences()->latest('id')->firstOrFail();
        $this->assertNotSame($old->idempotencyKey(), $new->idempotencyKey());
        app(OccurrenceExecutor::class)->execute($old->id);
        app(OccurrenceExecutor::class)->execute($new->id);
        $this->assertSame('skipped', $old->fresh()->status);
        $this->assertSame('succeeded', $new->fresh()->status);
        $this->assertSame(1, $this->definition->runs);
    }

    public function test_execution_rechecks_application_state(): void
    {
        $occurrence = $this->occurrence();
        $this->definition->continue = false;
        app(OccurrenceExecutor::class)->execute($occurrence->id);
        $this->assertSame('skipped', $occurrence->fresh()->status);
        $this->assertSame(0, $this->definition->runs);
    }

    public function test_scheduler_cancels_when_continuation_fails(): void
    {
        $sequence = $this->start();
        $this->definition->continue = false;
        $this->assertSame(0, app(DueSequenceRunner::class)->run());
        $this->assertSame('cancelled', $sequence->fresh()->status);
        $this->assertDatabaseCount('schedule_outbox', 0);
    }

    public function test_deleted_entity_is_skipped(): void
    {
        $occurrence = $this->occurrence();
        $occurrence->sequence->entity->delete();
        app(OccurrenceExecutor::class)->execute($occurrence->id);
        $this->assertSame('skipped', $occurrence->fresh()->status);
    }

    public function test_execution_retries_have_stable_identity_and_terminal_failure_can_be_retried(): void
    {
        $occurrence = $this->occurrence();
        $this->definition->fail = true;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                app(OccurrenceExecutor::class)->execute($occurrence->id);
                $this->assertSame(3, $attempt);
            } catch (RuntimeException $exception) {
                $this->assertLessThan(3, $attempt);
            }
        }
        $this->assertSame('failed', $occurrence->fresh()->status);
        $this->assertSame(3, $occurrence->fresh()->attempts);
        $this->assertCount(1, array_unique($this->definition->keys));
        app(SequenceManager::class)->retry($occurrence);
        $this->definition->fail = false;
        app(OccurrenceExecutor::class)->execute($occurrence->id);
        $this->assertSame('succeeded', $occurrence->fresh()->status);
        $this->assertCount(1, array_unique($this->definition->keys));
        $this->assertDatabaseCount('scheduled_occurrences', 1);
    }

    public function test_live_execution_lease_blocks_duplicates_and_expired_lease_is_recovered(): void
    {
        $occurrence = $this->occurrence();
        $occurrence->update(['status' => 'running', 'attempts' => 1, 'claim_token' => 'dead-worker', 'lease_until' => now('UTC')->addMinute()]);
        $this->assertFalse(app(OccurrenceExecutor::class)->execute($occurrence->id));
        $this->travel(61)->seconds();
        $this->assertTrue(app(OccurrenceExecutor::class)->execute($occurrence->id));
        $this->assertSame('succeeded', $occurrence->fresh()->status);
        $this->assertSame(2, $occurrence->fresh()->attempts);
    }

    public function test_repeated_worker_crashes_eventually_become_queryable_failures(): void
    {
        $occurrence = $this->occurrence();
        $occurrence->update(['status' => 'running', 'attempts' => 3, 'lease_until' => now('UTC')->subSecond()]);
        app(OccurrenceExecutor::class)->execute($occurrence->id);
        $this->assertSame('failed', $occurrence->fresh()->status);
        $this->assertSame(0, $this->definition->runs);
    }

    public function test_interrupted_publication_retries_with_the_same_occurrence(): void
    {
        $occurrence = $this->occurrence();
        $sent = [];
        $queue = Mockery::mock(QueueContract::class);
        $queue->shouldReceive('push')->twice()->andReturnUsing(function ($job) use (&$sent) {
            $sent[] = $job->occurrenceId;
            if (count($sent) === 1) {
                throw new RuntimeException('Published, then connection lost before acknowledgement');
            }

            return 'accepted';
        });
        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('connection')->with('database')->twice()->andReturn($queue);
        $publisher = new OutboxPublisher($factory);
        $this->assertSame(0, $publisher->publish());
        $this->assertNotNull($occurrence->outbox->fresh()->last_error);
        $this->travel(6)->seconds();
        $this->assertSame(1, $publisher->publish());
        $this->assertSame([$occurrence->id, $occurrence->id], $sent);
        foreach ($sent as $id) {
            app(OccurrenceExecutor::class)->execute($id);
        }
        $this->assertSame(1, $this->definition->runs);
    }

    public function test_publisher_recovers_abandoned_claim_after_lease_expires(): void
    {
        Queue::fake();
        $occurrence = $this->occurrence();
        $occurrence->outbox->update(['lease_until' => now('UTC')->addMinute(), 'claim_token' => 'dead-publisher']);
        $this->assertSame(0, app(OutboxPublisher::class)->publish());
        $this->travel(61)->seconds();
        $this->assertSame(1, app(OutboxPublisher::class)->publish());
        Queue::assertPushed(ExecuteScheduledOccurrence::class, 1);
    }

    public function test_recurring_downtime_emits_one_overdue_occurrence_then_skips_to_future_anchor(): void
    {
        $this->definition->steps = ['PT0S'];
        $this->definition->repeat = 3600;
        $sequence = $this->start();
        app(DueSequenceRunner::class)->run();
        $this->travel(10)->hours();
        $this->assertSame(1, app(DueSequenceRunner::class)->run());
        $this->assertSame(11, $sequence->fresh()->next_number);
        $this->assertTrue($sequence->fresh()->next_at->equalTo(CarbonImmutable::parse('2026-01-10 23:00:00', 'UTC')));
        $this->assertDatabaseCount('scheduled_occurrences', 2);
    }

    public function test_calendar_offsets_preserve_local_time_across_daylight_saving(): void
    {
        $this->definition->steps = ['P1D'];
        $sequence = app(SequenceManager::class)->start(
            'test-v1', SequenceEntity::query()->create(['name' => 'Example']),
            CarbonImmutable::parse('2026-03-07 09:00:00', 'America/New_York'),
            'America/New_York',
        );
        $this->assertSame('2026-03-08 13:00:00', $sequence->next_at->format('Y-m-d H:i:s'));
    }

    public function test_invalid_offsets_are_rejected_without_persisting_state(): void
    {
        $this->definition->steps = ['P2D', 'P1D'];
        try {
            $this->start();
            $this->fail('Unordered offsets should be rejected.');
        } catch (\InvalidArgumentException) {
            $this->assertDatabaseCount('scheduled_sequences', 0);
        }
    }

    public function test_definition_schedule_is_snapshotted_when_sequence_starts(): void
    {
        $sequence = $this->start();
        $this->definition->steps = ['P10D'];
        app(DueSequenceRunner::class)->materialize($sequence->id);
        $this->assertTrue($sequence->fresh()->next_at->equalTo(now('UTC')->addDay()));
    }

    public function test_unique_database_constraint_rejects_duplicate_occurrence_identity(): void
    {
        $occurrence = $this->occurrence();
        $this->expectException(QueryException::class);
        ScheduledOccurrence::query()->create($occurrence->only(['sequence_id', 'revision', 'number', 'scheduled_at', 'status']));
    }

    public function test_inspection_and_management_commands(): void
    {
        $sequence = $this->start();
        $this->artisan('sequences:list', ['--status' => 'active'])->expectsOutputToContain('test-v1')->assertSuccessful();
        $this->artisan('sequences:run', ['--limit' => 1])->expectsOutputToContain('Occurrences scheduled: 1')->assertSuccessful();
        $this->artisan('sequences:show', ['sequence' => $sequence->id])->expectsOutputToContain('"outbox"')->assertSuccessful();
        $this->artisan('sequences:cancel', ['sequence' => $sequence->id])->assertSuccessful();
        $this->assertSame('cancelled', $sequence->fresh()->status);
    }

    public function test_real_database_queue_round_trip(): void
    {
        $this->definition->steps = ['PT0S'];
        $occurrence = $this->occurrence();
        $this->assertSame(1, app(OutboxPublisher::class)->publish());
        $job = Queue::connection('database')->pop('scheduled-actions');
        $this->assertNotNull($job);
        $job->fire();
        $job->delete();
        $this->assertSame('succeeded', $occurrence->fresh()->status);
        $this->assertSame(1, $this->definition->runs);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_non_durable_queue_configuration_keeps_outbox_unpublished(): void
    {
        config(['scheduling.connection' => 'sync']);
        $occurrence = $this->occurrence();
        $this->assertSame(0, app(OutboxPublisher::class)->publish());
        $this->assertNull($occurrence->outbox->fresh()->published_at);
        $this->assertStringContainsString('durable', $occurrence->outbox->fresh()->last_error);
        $this->assertSame(0, $this->definition->runs);
    }

    public function test_queue_after_commit_configuration_does_not_defer_outbox_publication(): void
    {
        Queue::fake();
        config(['queue.connections.database.after_commit' => true]);
        $this->occurrence();
        $this->assertSame(1, app(OutboxPublisher::class)->publish());
        Queue::assertPushed(ExecuteScheduledOccurrence::class, fn ($job) => $job->afterCommit === false);
    }

    public function test_publication_inside_an_application_transaction_is_rejected(): void
    {
        DB::beginTransaction();
        try {
            $this->occurrence();
            app(OutboxPublisher::class)->publish();
            $this->fail('Uncommitted occurrences must never be published.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('after scheduling state has committed', $exception->getMessage());
            $this->assertDatabaseCount('jobs', 0);
        } finally {
            DB::rollBack();
        }
        $this->assertDatabaseCount('schedule_outbox', 0);
    }

    public function test_unknown_definition_is_visible_and_does_not_block_other_sequences(): void
    {
        $broken = $this->start();
        $broken->update(['definition' => 'missing-v1']);
        $healthy = $this->start();
        $this->assertSame(1, app(DueSequenceRunner::class)->run());
        $this->assertStringContainsString('Unknown sequence', $broken->fresh()->last_error);
        $this->assertSame(0, $broken->fresh()->next_number);
        $this->assertSame(1, $healthy->fresh()->next_number);
    }

    public function test_failed_stale_occurrences_cannot_be_retried(): void
    {
        $occurrence = $this->occurrence();
        $occurrence->update(['status' => 'failed']);
        app(SequenceManager::class)->cancel($occurrence->sequence);
        $this->expectException(\InvalidArgumentException::class);
        app(SequenceManager::class)->retry($occurrence);
    }
}

class TestSequence extends SequenceDefinition
{
    public array $steps = ['PT0S', 'P1D', 'P3D'];

    public ?int $repeat = null;

    public bool $continue = true;

    public bool $fail = false;

    public int $runs = 0;

    public array $keys = [];

    public function offsets(): array
    {
        return $this->steps;
    }

    public function repeatEverySeconds(): ?int
    {
        return $this->repeat;
    }

    public function shouldContinue(Model $entity): bool
    {
        return $this->continue;
    }

    public function execute(Model $entity, ScheduledOccurrence $occurrence): void
    {
        $this->runs++;
        $this->keys[] = $occurrence->idempotencyKey();
        if ($this->fail) {
            throw new RuntimeException('Injected action failure');
        }
    }
}
