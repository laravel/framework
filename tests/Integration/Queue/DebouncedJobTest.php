<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\DebounceLock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeDebounced;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Events\JobDebounced;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use LogicException;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class DebouncedJobTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('cache.default', 'database');
    }

    public function testDebouncedJobDispatchesAndExecutes()
    {
        DebouncedTestJob::$handled = false;

        dispatch(new DebouncedTestJob('entity-1'));
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue(DebouncedTestJob::$handled);
    }

    public function testSupersededDebouncedJobIsSkipped()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        DebouncedTestJob::$handleCount = 0;

        // Dispatch two jobs with the same debounce identity.
        // The second dispatch supersedes the first.
        dispatch(new DebouncedTestJob('entity-1'));
        dispatch(new DebouncedTestJob('entity-1'));

        // Process both jobs from the queue.
        $this->runQueueWorkerCommand(['--once' => true], 2);

        // Only the second (latest) dispatch should have executed.
        $this->assertEquals(1, DebouncedTestJob::$handleCount);
    }

    public function testLockIsReleasedForSuccessfulJobs()
    {
        DebouncedTestJob::$handled = false;

        dispatch($job = new DebouncedTestJob('entity-1'));
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);

        // Lock should be released — we can acquire it again.
        $this->assertTrue(
            $this->app->get(Cache::class)->lock(DebounceLock::getKey($job), 10)->get()
        );
    }

    public function testLockIsReleasedForFailedJobs()
    {
        DebouncedTestFailJob::$handled = false;

        $this->expectException(Exception::class);

        try {
            dispatch_sync($job = new DebouncedTestFailJob('entity-1'));
        } finally {
            $this->assertTrue($job::$handled);
            $this->assertTrue(
                $this->app->get(Cache::class)->lock(DebounceLock::getKey($job), 10)->get()
            );
        }
    }

    public function testJobDebouncedEventFiresForSupersededJob()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        Event::fake([JobDebounced::class]);

        dispatch(new DebouncedTestJob('entity-1'));
        dispatch(new DebouncedTestJob('entity-1'));

        $this->runQueueWorkerCommand(['--once' => true], 2);

        Event::assertDispatched(JobDebounced::class, 1);
    }

    public function testDebouncedAndUniqueThrowsLogicException()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('ShouldBeDebounced and ShouldBeUnique');

        DebouncedAndUniqueTestJob::dispatch('entity-1');
    }

    public function testDebounceOwnerSurvivesSerialization()
    {
        $job = new DebouncedTestJob('entity-1');
        $job->debounceOwner = 'test-owner-token-123';

        $restored = unserialize(serialize($job));

        $this->assertEquals('test-owner-token-123', $restored->debounceOwner);
    }

    public function testDifferentDebounceIdsDoNotInterfere()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        DebouncedTestJob::$handleCount = 0;

        dispatch(new DebouncedTestJob('entity-1'));
        dispatch(new DebouncedTestJob('entity-2'));

        $this->runQueueWorkerCommand(['--once' => true], 2);

        // Both should execute — different identities.
        $this->assertEquals(2, DebouncedTestJob::$handleCount);
    }

    public function testDebounceLockKeyFormat()
    {
        $job = new DebouncedTestJob('entity-1');

        $key = DebounceLock::getKey($job);

        $this->assertStringStartsWith('laravel_debounced_job:', $key);
        $this->assertStringEndsWith(':entity-1', $key);
    }

    public function testQueueFakeCapturesDebouncedJob()
    {
        Queue::fake();

        DebouncedTestJob::dispatch('entity-1');

        Queue::assertPushed(DebouncedTestJob::class);
    }
}

class DebouncedTestJob implements ShouldQueue, ShouldBeDebounced
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public static $handled = false;

    public static $handleCount = 0;

    public string $debounceOwner = '';

    public function __construct(public string $entityId)
    {
    }

    public function debounceId(): string
    {
        return $this->entityId;
    }

    public function debounceFor(): int
    {
        return 30;
    }

    public function handle()
    {
        static::$handled = true;
        static::$handleCount++;
    }
}

class DebouncedTestFailJob implements ShouldQueue, ShouldBeDebounced
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public $tries = 1;

    public static $handled = false;

    public string $debounceOwner = '';

    public function __construct(public string $entityId)
    {
    }

    public function debounceId(): string
    {
        return $this->entityId;
    }

    public function debounceFor(): int
    {
        return 30;
    }

    public function handle()
    {
        static::$handled = true;

        throw new Exception;
    }
}

class DebouncedAndUniqueTestJob implements ShouldQueue, ShouldBeDebounced, ShouldBeUnique
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public string $debounceOwner = '';

    public function __construct(public string $entityId)
    {
    }

    public function debounceId(): string
    {
        return $this->entityId;
    }

    public function debounceFor(): int
    {
        return 30;
    }

    public function handle()
    {
    }
}
