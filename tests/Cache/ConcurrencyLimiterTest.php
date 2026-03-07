<?php

namespace Illuminate\Tests\Cache;

use BadMethodCallException;
use Error;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Limiters\ConcurrencyLimiter;
use Illuminate\Cache\Limiters\LimiterTimeoutException;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store;
use PHPUnit\Framework\TestCase;
use Throwable;

class ConcurrencyLimiterTest extends TestCase
{
    protected Repository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new Repository(new ArrayStore);
    }

    public function testItLocksTasksWhenNoSlotAvailable()
    {
        $store = [];

        foreach (range(1, 2) as $i) {
            (new ConcurrencyLimiterMockThatDoesntRelease($this->repository->getStore(), 'key', 2, 5))->block(2, function () use (&$store, $i) {
                $store[] = $i;
            });
        }

        try {
            (new ConcurrencyLimiterMockThatDoesntRelease($this->repository->getStore(), 'key', 2, 5))->block(0, function () use (&$store) {
                $store[] = 3;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        (new ConcurrencyLimiterMockThatDoesntRelease($this->repository->getStore(), 'other_key', 2, 5))->block(2, function () use (&$store) {
            $store[] = 4;
        });

        $this->assertEquals([1, 2, 4], $store);
    }

    public function testItReleasesLockAfterTaskFinishes()
    {
        $store = [];

        foreach (range(1, 4) as $i) {
            (new ConcurrencyLimiter($this->repository->getStore(), 'key', 2, 5))->block(2, function () use (&$store, $i) {
                $store[] = $i;
            });
        }

        $this->assertEquals([1, 2, 3, 4], $store);
    }

    public function testItReleasesLockIfTaskTookTooLong()
    {
        $store = [];

        $lock = new ConcurrencyLimiterMockThatDoesntRelease($this->repository->getStore(), 'key', 1, 1);

        $lock->block(2, function () use (&$store) {
            $store[] = 1;
        });

        try {
            $lock->block(0, function () use (&$store) {
                $store[] = 2;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        usleep(1.2 * 1000000);

        $lock->block(0, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 3], $store);
    }

    public function testItFailsImmediatelyOrRetriesForAWhileBasedOnAGivenTimeout()
    {
        $store = [];

        $lock = new ConcurrencyLimiterMockThatDoesntRelease($this->repository->getStore(), 'key', 1, 2);

        $lock->block(2, function () use (&$store) {
            $store[] = 1;
        });

        try {
            $lock->block(0, function () use (&$store) {
                $store[] = 2;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        $lock->block(3, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 3], $store);
    }

    public function testItFailsAfterRetryTimeout()
    {
        $store = [];

        $lock = new ConcurrencyLimiterMockThatDoesntRelease($this->repository->getStore(), 'key', 1, 10);

        $lock->block(2, function () use (&$store) {
            $store[] = 1;
        });

        try {
            $lock->block(2, function () use (&$store) {
                $store[] = 2;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        $this->assertEquals([1], $store);
    }

    public function testItReleasesIfErrorIsThrown()
    {
        $store = [];

        $lock = new ConcurrencyLimiter($this->repository->getStore(), 'key', 1, 5);

        try {
            $lock->block(1, function () {
                throw new Error;
            });
        } catch (Error) {
        }

        $lock = new ConcurrencyLimiter($this->repository->getStore(), 'key', 1, 5);
        $lock->block(1, function () use (&$store) {
            $store[] = 1;
        });

        $this->assertEquals([1], $store);
    }

    public function testFunnelMethodOnRepository()
    {
        $store = [];

        $result = $this->repository->funnel('test-funnel')
            ->limit(2)
            ->releaseAfter(5)
            ->block(2)
            ->then(function () use (&$store) {
                $store[] = 1;

                return 'ok';
            });

        $this->assertEquals([1], $store);
        $this->assertSame('ok', $result);
    }

    public function testFunnelMethodAcceptsBackedEnum()
    {
        $store = [];

        $result = $this->repository->funnel(ConcurrencyLimiterBackedEnum::TestFunnel)
            ->limit(2)
            ->releaseAfter(5)
            ->block(2)
            ->then(function () use (&$store) {
                $store[] = 1;

                return 'ok';
            });

        $this->assertEquals([1], $store);
        $this->assertSame('ok', $result);
    }

    public function testFunnelMethodAcceptsUnitEnum()
    {
        $store = [];

        $result = $this->repository->funnel(ConcurrencyLimiterUnitEnum::TestFunnel)
            ->limit(2)
            ->releaseAfter(5)
            ->block(2)
            ->then(function () use (&$store) {
                $store[] = 1;

                return 'ok';
            });

        $this->assertEquals([1], $store);
        $this->assertSame('ok', $result);
    }

    public function testFunnelBackedEnumSharesKeyWithStringEquivalent()
    {
        // Fill all slots using the backed enum's string value
        foreach (range(1, 2) as $i) {
            (new ConcurrencyLimiterMockThatDoesntRelease($this->repository->getStore(), 'test-funnel', 2, 5))->block(2, function () {
            });
        }

        // Try to acquire via the BackedEnum — should conflict with the string key
        $result = $this->repository->funnel(ConcurrencyLimiterBackedEnum::TestFunnel)
            ->limit(2)
            ->releaseAfter(5)
            ->block(0)
            ->then(
                function () {
                    return 'success';
                },
                function () {
                    return 'failed';
                }
            );

        $this->assertSame('failed', $result);
    }

    public function testFunnelThrowsExceptionWhenStoreDoesNotSupportLocks()
    {
        $store = $this->createMock(Store::class);
        $repository = new Repository($store);

        $this->assertNotInstanceOf(LockProvider::class, $store);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('This cache store does not support locks.');

        $repository->funnel('test');
    }

    public function testFunnelWithFailureCallback()
    {
        $store = [];

        // Fill all slots without releasing
        foreach (range(1, 2) as $i) {
            (new ConcurrencyLimiterMockThatDoesntRelease($this->repository->getStore(), 'funnel-key', 2, 5))->block(2, function () use (&$store, $i) {
                $store[] = $i;
            });
        }

        // Try to acquire when all slots are full
        $result = $this->repository->funnel('funnel-key')
            ->limit(2)
            ->releaseAfter(5)
            ->block(0)
            ->then(
                function () use (&$store) {
                    $store[] = 'success';
                },
                function ($e) use (&$store) {
                    $this->assertInstanceOf(LimiterTimeoutException::class, $e);
                    $store[] = 'failed';

                    return 'failure-result';
                }
            );

        $this->assertEquals([1, 2, 'failed'], $store);
        $this->assertSame('failure-result', $result);
    }
}

class ConcurrencyLimiterMockThatDoesntRelease extends ConcurrencyLimiter
{
    protected function release($lock)
    {
        //
    }
}

enum ConcurrencyLimiterBackedEnum: string
{
    case TestFunnel = 'test-funnel';
}

enum ConcurrencyLimiterUnitEnum
{
    case TestFunnel;
}
