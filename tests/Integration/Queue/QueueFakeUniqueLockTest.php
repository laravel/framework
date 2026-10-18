<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\Concerns\CreatesApplication;
use PHPUnit\Framework\Attributes\TestWith;

class QueueFakeUniqueLockTest extends TestCase
{
    use CreatesApplication;

    protected static $store;

    protected function defineEnvironment($app)
    {
        $store = static::$store ??= new ArrayStore;

        $app['config']->set('cache.default', 'persistent');
        $app['config']->set('cache.stores.persistent', ['driver' => 'persistent']);

        $app['cache']->extend('persistent', fn () => $this->repository($store));
    }

    #[TestWith(['first test'])]
    #[TestWith(['second test'])]
    public function test_unique_job_locks_do_not_leak_into_the_next_test(string $test)
    {
        $this->assertSame([], static::$store->locks);

        Queue::fake();

        dispatch(new QueueFakeUniqueJob);
        dispatch(new QueueFakeUniqueJob);

        Queue::assertPushedTimes(QueueFakeUniqueJob::class, 1);
    }
}

class QueueFakeUniqueJob implements ShouldQueue, ShouldBeUnique
{
    //
}
