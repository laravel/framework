<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\Attributes\WithMigration;

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
        UniqueTestFailJobWithSerializedModels::$handled = false;
        $user = User::factoryCreate();
        $user->delete();
        $this->expectException(ModelNotFoundException::class);

        try {
            dispatch($job = (new UniqueTestFailJobWithSerializedModels($user)));
            $this->runQueueWorkerCommand(['--once' => true]);
            Queue::assertPushed(UniqueTestFailJobWithSerializedModels::class, 1);
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

class UniqueTestFailJobWithSerializedModels implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public static $handled = false;

    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle()
    {
        static::$handled = true;
    }
}

class User extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function factoryCreate()
    {
        return self::create([
            'name' => 'test user',
            'email' => 'testUser@test.com',
            'password' => Hash::make('password'),
        ]);
    }
}
