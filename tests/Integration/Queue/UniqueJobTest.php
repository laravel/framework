<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class UniqueJobTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('cache.default', 'database');
    }

    public function testUniqueJobsAreNotDispatched()
    {
        Bus::fake();

        UniqueTestJob::dispatch();
        $this->runQueueWorkerCommand(['--once' => true]);
        Bus::assertDispatched(UniqueTestJob::class);

        $this->assertFalse(
            $this->app->get(Cache::class)->lock($this->getLockKey(UniqueTestJob::class), 10)->get()
        );

        Bus::assertDispatchedTimes(UniqueTestJob::class);
        UniqueTestJob::dispatch();
        $this->runQueueWorkerCommand(['--once' => true]);
        Bus::assertDispatchedTimes(UniqueTestJob::class);

        $this->assertFalse(
            $this->app->get(Cache::class)->lock($this->getLockKey(UniqueTestJob::class), 10)->get()
        );
    }

    public function testUniqueJobWithViaDispatched()
    {
        Bus::fake();

        UniqueViaJob::dispatch();
        Bus::assertDispatched(UniqueViaJob::class);
    }

    public function testLockIsReleasedForSuccessfulJobs()
    {
        UniqueTestJob::$handled = false;
        dispatch($job = new UniqueTestJob);
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);
        $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
    }

    public function testLockIsReleasedForFailedJobs()
    {
        UniqueTestFailJob::$handled = false;

        $this->expectException(Exception::class);

        try {
            dispatch_sync($job = new UniqueTestFailJob);
        } finally {
            $this->assertTrue($job::$handled);
            $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
        }
    }

    public function testLockIsNotReleasedForJobRetries()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        UniqueTestRetryJob::$handled = false;

        dispatch($job = new UniqueTestRetryJob);

        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);
        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        UniqueTestRetryJob::$handled = false;
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);
        $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
    }

    public function testLockIsNotReleasedForJobReleases()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        UniqueTestReleasedJob::$handled = false;
        dispatch($job = new UniqueTestReleasedJob);

        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);
        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        UniqueTestReleasedJob::$handled = false;
        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertFalse($job::$handled);
        $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
    }

    public function testLockCanBeReleasedBeforeProcessing()
    {
        $this->markTestSkippedWhenUsingSyncQueueDriver();

        UniqueUntilStartTestJob::$handled = false;

        dispatch($job = new UniqueUntilStartTestJob);

        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());

        $this->runQueueWorkerCommand(['--once' => true]);

        $this->assertTrue($job::$handled);
        $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
    }

    public function testLockIsReleasedOnModelNotFoundException()
    {
        UniqueTestSerializesModelsJob::$handled = false;

        /** @var \Illuminate\Foundation\Auth\User */
        $user = UserFactory::new()->create();
        $job = new UniqueTestSerializesModelsJob($user);

        $this->expectException(ModelNotFoundException::class);

        try {
            $user->delete();
            dispatch($job);
            $this->runQueueWorkerCommand(['--once' => true]);
            unserialize(serialize($job));
        } finally {
            $this->assertFalse($job::$handled);
            $this->assertModelMissing($user);
            $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey($job), 10)->get());
        }
    }

    protected function getLockKey($job)
    {
        return 'laravel_unique_job:'.(is_string($job) ? $job : get_class($job)).':';
    }
}

class UniqueTestJob implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }
}

class UniqueTestFailJob implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public $tries = 1;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;

        throw new Exception;
    }
}

class UniqueTestReleasedJob extends UniqueTestFailJob
{
    public $tries = 1;

    public function handle()
    {
        static::$handled = true;

        $this->release();
    }
}

class UniqueTestRetryJob extends UniqueTestFailJob
{
    public $tries = 2;
}

class UniqueUntilStartTestJob extends UniqueTestJob implements ShouldBeUniqueUntilProcessing
{
    public $tries = 2;
}

class UniqueTestSerializesModelsJob extends UniqueTestJob
{
    use SerializesModels;

    public $deleteWhenMissingModels = true;

    public function __construct(public User $user)
    {
    }
}

class UniqueViaJob extends UniqueTestJob
{
    public function uniqueVia(): Cache
    {
        return Container::getInstance()->make(Cache::class);
    }
}
