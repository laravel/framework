<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Queueable;
use Illuminate\Bus\UniqueLock;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BusUniqueLockTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function test_acquire_records_the_owner_of_the_lock_on_the_job()
    {
        $cache = new Repository(new ArrayStore);

        $job = new UniqueLockTestJob;

        $this->assertTrue((new UniqueLock($cache))->acquire($job));

        $this->assertNotSame('', $job->uniqueLockOwner);

        // The recorded token is the real owner of the lock, so restoring the
        // lock with it releases the lock...
        $this->assertTrue(
            $cache->restoreLock(UniqueLock::getKey($job), $job->uniqueLockOwner)->release()
        );
        $this->assertTrue((new UniqueLock($cache))->acquire(new UniqueLockTestJob));
    }

    public function test_acquire_does_not_record_an_owner_when_the_lock_is_not_acquired()
    {
        $cache = new Repository(new ArrayStore);

        $this->assertTrue((new UniqueLock($cache))->acquire(new UniqueLockTestJob));

        $second = new UniqueLockTestJob;

        $this->assertFalse((new UniqueLock($cache))->acquire($second));
        $this->assertSame('', $second->uniqueLockOwner);
    }

    public function test_release_does_not_release_a_lock_owned_by_another_dispatch()
    {
        $cache = new Repository(new ArrayStore);

        $lock = new UniqueLock($cache);

        // The first dispatch acquires the lock and its own owner token...
        $stale = new UniqueLockTestJob;
        $this->assertTrue($lock->acquire($stale));

        // The lock is released before processing, and a replacement dispatch
        // acquires it with a brand new owner token...
        $lock->release($stale);
        $replacement = new UniqueLockTestJob;
        $this->assertTrue($lock->acquire($replacement));

        // A stale delivery of the first dispatch must not release the
        // replacement's lock...
        $lock->release($stale);

        $this->assertFalse($lock->acquire(new UniqueLockTestJob));

        // ...and the replacement is still able to release its own lock.
        $lock->release($replacement);

        $this->assertTrue($lock->acquire(new UniqueLockTestJob));
    }

    public function test_release_force_releases_payloads_that_have_no_recorded_owner()
    {
        $cache = new Repository(new ArrayStore);

        $lock = new UniqueLock($cache);

        $this->assertTrue($lock->acquire(new UniqueLockTestJob));

        // Payloads queued before an upgrade carry no owner token, so they must
        // keep the previous force release behaviour...
        $legacy = new UniqueLockTestJob;
        $this->assertSame('', $legacy->uniqueLockOwner);

        $lock->release($legacy);

        $this->assertTrue($cache->lock(UniqueLock::getKey($legacy))->get());
    }

    public function test_release_force_releases_a_payload_serialized_before_the_owner_property_existed()
    {
        $cache = new Repository(new ArrayStore);

        $lock = new UniqueLock($cache);

        $this->assertTrue($lock->acquire(new UniqueLockTestJob));

        $class = UniqueLockTestJob::class;
        $legacy = unserialize(sprintf('O:%d:"%s":0:{}', strlen($class), $class));

        $this->assertInstanceOf(UniqueLockTestJob::class, $legacy);
        $this->assertSame('', $legacy->uniqueLockOwner);

        $lock->release($legacy);

        $this->assertTrue($cache->lock(UniqueLock::getKey($legacy))->get());
    }

    public function test_release_force_releases_when_the_store_does_not_provide_locks()
    {
        $cacheLock = m::mock(Lock::class);
        $cacheLock->shouldReceive('get')->once()->andReturn(true);
        $cacheLock->shouldNotReceive('owner');
        $cacheLock->shouldReceive('forceRelease')->once();

        $store = m::mock(Store::class);
        $store->shouldReceive('lock')->twice()->andReturn($cacheLock);

        $job = new UniqueLockTestJob;

        $lock = new UniqueLock(new Repository($store));

        $this->assertTrue($lock->acquire($job));
        $this->assertSame('', $job->uniqueLockOwner);

        $lock->release($job);
    }

    public function test_acquire_does_not_create_dynamic_properties_on_jobs_without_the_owner_property()
    {
        $cache = new Repository(new ArrayStore);

        $job = new UniqueLockTestJobWithoutQueueable;

        $this->assertTrue((new UniqueLock($cache))->acquire($job));
        $this->assertFalse(property_exists($job, 'uniqueLockOwner'));
    }

    public function test_jobs_without_queueable_may_define_their_own_owner_property()
    {
        foreach ([
            new UniqueLockTestJobWithPrivateOwner,
            new UniqueLockTestJobWithProtectedOwner,
            new UniqueLockTestJobWithReadonlyOwner,
        ] as $job) {
            $cache = new Repository(new ArrayStore);
            $lock = new UniqueLock($cache);

            $this->assertTrue($lock->acquire($job));
            $this->assertSame('application-owner', $job->ownerValue());

            $lock->release($job);

            $this->assertTrue($cache->lock(UniqueLock::getKey($job))->get());
        }
    }

    public function test_unique_via_is_used_when_releasing_by_owner()
    {
        $default = new Repository(new ArrayStore);
        $alternate = new Repository(new ArrayStore);

        UniqueViaLockTestJob::$cache = $alternate;

        $job = new UniqueViaLockTestJob;
        $lock = new UniqueLock($default);

        $this->assertTrue($lock->acquire($job));

        $defaultLock = $default->lock(UniqueLock::getKey($job));
        $this->assertTrue($defaultLock->get());
        $this->assertFalse($alternate->lock(UniqueLock::getKey($job))->get());

        $lock->release($job);

        $this->assertTrue($alternate->lock(UniqueLock::getKey($job))->get());

        $defaultLock->release();
    }
}

class UniqueLockTestJob implements ShouldBeUnique
{
    use Queueable;

    public function uniqueId()
    {
        return 'unique-id';
    }
}

class UniqueLockTestJobWithoutQueueable implements ShouldBeUnique
{
    public function uniqueId()
    {
        return 'unique-id';
    }
}

class UniqueLockTestJobWithPrivateOwner extends UniqueLockTestJobWithoutQueueable
{
    private $uniqueLockOwner = 'application-owner';

    public function ownerValue()
    {
        return $this->uniqueLockOwner;
    }
}

class UniqueLockTestJobWithProtectedOwner extends UniqueLockTestJobWithoutQueueable
{
    protected $uniqueLockOwner = 'application-owner';

    public function ownerValue()
    {
        return $this->uniqueLockOwner;
    }
}

class UniqueLockTestJobWithReadonlyOwner extends UniqueLockTestJobWithoutQueueable
{
    public readonly string $uniqueLockOwner;

    public function __construct()
    {
        $this->uniqueLockOwner = 'application-owner';
    }

    public function ownerValue()
    {
        return $this->uniqueLockOwner;
    }
}

class UniqueViaLockTestJob extends UniqueLockTestJob
{
    public static $cache;

    public function uniqueVia(): Cache
    {
        return static::$cache;
    }
}
