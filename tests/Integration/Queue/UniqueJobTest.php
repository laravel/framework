<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\UniqueJob;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Mockery as m;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class UniqueJobTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testNonUniqueJobsAreDispatched()
    {
        Bus::fake();
        UniqueTestJob::dispatch();
        Bus::assertDispatched(UniqueTestJob::class);

        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey(), 10)->get());

        Bus::fake();
        UniqueTestJob::dispatch();
        Bus::assertNotDispatched(UniqueTestJob::class);

        $this->assertFalse($this->app->get(Cache::class)->lock($this->getLockKey(), 10)->get());
    }

    public function testLockIsReleased()
    {
        UniqueTestJob::$handled = false;
        dispatch($job = new UniqueTestJob);
        $this->assertTrue($job::$handled);

        $this->assertTrue($this->app->get(Cache::class)->lock($this->getLockKey(), 10)->get());
    }

    protected function getLockKey()
    {
        return 'unique:'.UniqueTestJob::class;
    }
}

class UniqueTestJob implements ShouldQueue, UniqueJob
{
    use InteractsWithQueue, Queueable, Dispatchable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }
}
