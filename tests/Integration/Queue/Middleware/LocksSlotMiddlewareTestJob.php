<?php

namespace Illuminate\Tests\Integration\Queue\Middleware;

use Exception;
use LogicException;
use Mockery as m;
use Illuminate\Bus\Dispatcher;
use Illuminate\Queue\JobLocker;
use Illuminate\Routing\Pipeline;
use Orchestra\Testbench\TestCase;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Queue\HandlesRaceCondition;
use Illuminate\Contracts\Container\Container;
use Illuminate\Queue\Middleware\RaceConditionJobMiddleware;

/**
 * @group integration
 */
class LocksSlotJobMiddlewareTest extends TestCase
{
    public function testReceivesNonLockableJob()
    {
        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);
        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldNotReceive('isDeleted');
        $job->shouldReceive('isReleased')->once()->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize(new NonLockableTestJob),
        ]);

        $this->assertTrue(NonLockableTestJob::$handled);

        NonLockableTestJob::$handled = false;
    }

    public function testFailsIfJobLacksInteractsWithQueueTrait()
    {
        $this->expectException(LogicException::class);

        $instance = new CallQueuedHandler(new Dispatcher(app()));
        $job = m::mock(Job::class);

        $instance->call($job, [
            'command' => serialize(new WithoutTraitTestJob),
        ]);
    }

    public function testReceivesLockableJob()
    {
        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);
        $job->shouldReceive('hasFailed')->twice()->andReturn(false);
        $job->shouldNotReceive('isDeleted');
        $job->shouldReceive('isReleased')->once()->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->twice()->andReturn(false);
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize(new LockableTestJob),
        ]);

        $this->assertTrue(LockableTestJob::$handled);
        $this->assertEquals(1, LockableTestJob::$current_slot);

        LockableTestJob::$handled = false;
        LockableTestJob::$slots = [];
    }

    public function testJobDeletedReleasesSlot()
    {
        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);

        $job_deleted = new LockableTestJob;
        $job_deleted->should_throw = true;

        try {
            $instance->call($job, [
                'command' => serialize($job_deleted),
            ]);
        } catch (Exception $exception) {
            //
        }

        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);
        $job->shouldReceive('isDeletedOrReleased')->twice()->andReturnFalse();
        $job->shouldReceive('hasFailed')->twice()->andReturnFalse();
        $job->shouldReceive('isReleased')->once()->andReturnFalse();
        $job->shouldNotReceive('isDeleted');
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize(new LockableTestJob),
        ]);

        $this->assertTrue(LockableTestJob::$handled);
        // Since the last job failed but the slot was released, the second will use the same slot
        $this->assertEquals([1], LockableTestJob::$slots);

        LockableTestJob::$handled = false;
        LockableTestJob::$slots = [];
    }

    public function testJobReleasedReleasesSlot()
    {
        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);
        $job->shouldReceive('release')->once();
        $job->shouldReceive('isDeletedOrReleased')->twice()->andReturnTrue();
        $job->shouldReceive('hasFailed')->once()->andReturnFalse();
        $job->shouldReceive('isReleased')->once()->andReturnTrue();

        $job_deleted = new LockableTestJob;
        $job_deleted->should_release = true;

        $instance->call($job, [
            'command' => serialize($job_deleted),
        ]);

        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);
        $job->shouldReceive('isDeletedOrReleased')->twice()->andReturnFalse();
        $job->shouldReceive('hasFailed')->twice()->andReturnFalse();
        $job->shouldReceive('isReleased')->once()->andReturnFalse();
        $job->shouldNotReceive('isDeleted');
        $job->shouldReceive('delete');

        $instance->call($job, [
            'command' => serialize(new LockableTestJob),
        ]);

        $this->assertTrue(LockableTestJob::$handled);
        $this->assertEquals([1, 1], LockableTestJob::$slots);

        LockableTestJob::$handled = false;
        LockableTestJob::$slots = [];
    }

    public function testJobThrowReleasesSlot()
    {
        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);

        $job_deleted = new LockableTestJob;
        $job_deleted->should_throw = true;

        try {
            $instance->call($job, [
                'command' => serialize($job_deleted),
            ]);
        } catch (Exception $exception) {
            //
        }

        $this->assertTrue(LockableTestJob::$handled);
        $this->assertEquals([], LockableTestJob::$slots);

        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);
        $job->shouldReceive('isDeletedOrReleased')->twice()->andReturnFalse();
        $job->shouldReceive('hasFailed')->twice()->andReturnFalse();
        $job->shouldReceive('isReleased')->once()->andReturnFalse();
        $job->shouldNotReceive('isDeleted');
        $job->shouldReceive('delete');

        $instance->call($job, [
            'command' => serialize(new LockableTestJob),
        ]);

        $this->assertTrue(LockableTestJob::$handled);
        $this->assertEquals([1], LockableTestJob::$slots);

        LockableTestJob::$handled = false;
        LockableTestJob::$slots = [];
    }

    public function testJobFailedReleasesSlot()
    {
        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);
        $job->shouldReceive('fail')->once();
        $job->shouldReceive('isDeletedOrReleased')->twice()->andReturnFalse();
        $job->shouldReceive('hasFailed')->twice()->andReturnTrue();
        $job->shouldReceive('delete')->once();

        $job_deleted = new LockableTestJob;
        $job_deleted->should_fail = true;

        $instance->call($job, [
            'command' => serialize($job_deleted),
        ]);

        $this->assertTrue(LockableTestJob::$handled);
        $this->assertEquals([1], LockableTestJob::$slots);

        $instance = new CallQueuedHandler(new Dispatcher(app()));

        $job = m::mock(Job::class);
        $job->shouldReceive('isDeletedOrReleased')->twice()->andReturnFalse();
        $job->shouldReceive('hasFailed')->twice()->andReturnFalse();
        $job->shouldReceive('isReleased')->once()->andReturnFalse();
        $job->shouldNotReceive('isDeleted');
        $job->shouldReceive('delete');

        $instance->call($job, [
            'command' => serialize(new LockableTestJob),
        ]);

        $this->assertTrue(LockableTestJob::$handled);
        // Since the first job failed, the next would use the same slot
        $this->assertEquals([1, 1], LockableTestJob::$slots);

        LockableTestJob::$handled = false;
        LockableTestJob::$slots = [];
    }

    public function testConcurrentJobs()
    {
        $command = new LockableTestJob;

        $job_0 = app(LockerTestJob::class, ['command' => $command, 'prefix' => 'locker', 'ttl' => 60]);
        $job_1 = app(LockerTestJob::class, ['command' => $command, 'prefix' => 'locker', 'ttl' => 60]);
        $job_2 = app(LockerTestJob::class, ['command' => $command, 'prefix' => 'locker', 'ttl' => 60]);
        $job_3 = app(LockerTestJob::class, ['command' => $command, 'prefix' => 'locker', 'ttl' => 60]);
        $job_4 = app(LockerTestJob::class, ['command' => $command, 'prefix' => 'locker', 'ttl' => 60]);
        $job_5 = app(LockerTestJob::class, ['command' => $command, 'prefix' => 'locker', 'ttl' => 60]);
        $job_6 = app(LockerTestJob::class, ['command' => $command, 'prefix' => 'locker', 'ttl' => 60]);

        $job_0->reserveNextAvailableSlot();
        $job_0->handleCommand();
        $job_1->reserveNextAvailableSlot();
        $job_1->handleCommand();
        $job_2->reserveNextAvailableSlot();
        $job_2->handleCommand(); // Stalls
        $job_1->releaseAndUpdateSlot(); // Inverse Order
        $job_0->releaseAndUpdateSlot(); // Inverse Order
        $job_3->reserveNextAvailableSlot();
        $job_3->handleCommand(); // Starts when other is stalled
        $job_3->releaseAndUpdateSlot(); // Ends when other is stalled
        $job_4->reserveNextAvailableSlot();
        $job_4->handleCommand();
        $job_4->releaseAndUpdateSlot();
        $job_2->releaseSlot(); // Stalls, fails and only releases slot
        $job_5->reserveNextAvailableSlot();
        $job_5->handleCommand();
        $job_6->reserveNextAvailableSlot();
        $job_6->handleCommand();
        $job_5->releaseAndUpdateSlot();
        $job_6->releaseAndUpdateSlot();

        $this->assertEquals([
            1, 2, 3, 4, 5, 6, 7,
        ], LockableTestJob::$slots);

        LockableTestJob::$handled = false;
        LockableTestJob::$slots = [];
    }

    public function testConcurrentJobFailsAndReleasesSlot()
    {
        $command = new LockableTestJob;
        $failCommand = new LockableTestJob;
        $failCommand->should_throw = true;

        $job_0 = app(LockerTestJob::class, ['command' => $command, 'prefix' => 'locker', 'ttl' => 60]);
        $job_1 = app(LockerTestJob::class, ['command' => $command, 'prefix' => 'locker', 'ttl' => 60]);
        $job_2 = app(LockerTestJob::class, ['command' => $failCommand, 'prefix' => 'locker', 'ttl' => 60]);
        $job_3 = app(LockerTestJob::class, ['command' => $command, 'prefix' => 'locker', 'ttl' => 60]);

        $job_0->reserveNextAvailableSlot();
        $job_0->handleCommand();
        $job_1->reserveNextAvailableSlot();
        $job_1->handleCommand();
        $job_2->reserveNextAvailableSlot();

        try {
            $job_2->handleCommand();
        } catch (Exception $exception) {
            $job_2->releaseSlot();
        }

        $job_0->releaseAndUpdateSlot();
        $job_1->releaseAndUpdateSlot();
        $job_3->reserveNextAvailableSlot();
        $job_3->handleCommand();
        $job_3->releaseAndUpdateSlot();

        // The third job didn't complete, so the fourth job would use the freed third slot
        $this->assertEquals([
            1, 2, 3,
        ], LockableTestJob::$slots);

        LockableTestJob::$handled = false;
        LockableTestJob::$slots = [];
    }

    public function testJobUsesCustomCacheTtlPrefix()
    {
        $custom = new CustomLockableTestJob();
        $cache = $custom->cache();
        $job = $custom->getJob();

        $cache->shouldReceive('remember')->once()
            ->with('test_prefix:last_slot', null, m::type('callable'))->andReturn(0);
        $cache->shouldReceive('has')->once()
            ->with('test_prefix|1')->andReturnFalse();
        $cache->shouldReceive('put')->once()
            ->with('test_prefix|1', m::type('float'), 99);
        $cache->shouldReceive('get')->once()
            ->with('test_prefix:microtime', 0)->andReturn(0);
        $cache->shouldReceive('get')->once()
            ->with('test_prefix|1', 0)->andReturn(1);
        $cache->shouldReceive('forever')->once()
            ->with('test_prefix:microtime', m::type('float'));
        $cache->shouldReceive('forever')->once()
            ->with('test_prefix:last_slot', 1);
        $cache->shouldReceive('forget')->once()
            ->with('test_prefix|1');

        $job->shouldReceive('isDeletedOrReleased')->once()->andReturnFalse();
        $job->shouldReceive('hasFailed')->once()->andReturnFalse();

        $result = (new Pipeline(app(Container::class)))
            ->send($custom)
            ->through($custom->middleware)
            ->then(function ($custom) {
                return $custom->handle();
            });

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }
}

class NonLockableTestJob
{
    use HandlesRaceCondition;
    use InteractsWithQueue;

    public static $handled = false;

    public function handle()
    {
        self::$handled = true;
    }
}

class LockableTestJob
{
    use HandlesRaceCondition;
    use InteractsWithQueue;

    public static $slots = [];

    public static $current_slot;

    public static $handled = false;

    public $middleware = [
        RaceConditionJobMiddleware::class,
    ];

    public $should_throw = false;
    public $should_fail = false;
    public $should_release = false;
    public $should_delete = false;

    public function handle()
    {
        self::$handled = true;
        self::$current_slot = $this->slot;

        if ($this->should_throw) {
            throw new Exception;
        }

        if ($this->should_release) {
            $this->release();
        }

        if ($this->should_fail) {
            $this->fail();
        }

        if ($this->should_delete) {
            $this->delete();
        }

        self::$slots[] = $this->slot;
    }
}

class CustomLockableTestJob extends LockableTestJob
{
    public $prefix = 'test_prefix';
    public $slotTtl = 99;
    public $cache;

    public function __construct()
    {
        $this->job = m::mock(Job::class);
    }

    public function handle()
    {
        return true;
    }

    public function setJob($job)
    {
        //
    }

    public function cache()
    {
        return $this->cache ?? $this->cache = m::mock(Repository::class);
    }
}

class TaggedCacheTestJob extends LockableTestJob
{
    public $cache;

    public function cache()
    {
        return $this->cache = m::mock(Repository::class);
    }

    public function handle()
    {
        return true;
    }
}

class LockerTestJob extends JobLocker
{
    public function handleCommand()
    {
        $this->command->handle(1, function ($result) {
            return $result;
        });
    }
}

class WithoutTraitTestJob
{
    use HandlesRaceCondition;

    public $middleware = [
        RaceConditionJobMiddleware::class,
    ];

    public function handle()
    {
        return true;
    }
}
