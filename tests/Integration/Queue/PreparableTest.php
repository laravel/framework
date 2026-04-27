<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Preparable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class PreparableTest extends TestCase
{
    public function test_before_dispatch_is_false()
    {
        Queue::fake();

        PreparableFalseJob::dispatch();

        Queue::assertNotPushed(PreparableFalseJob::class);
    }

    public function test_before_dispatch_is_void()
    {
        Queue::fake();

        PreparableVoidJob::$ran = false;

        PreparableVoidJob::dispatch();

        $this->assertTrue(PreparableVoidJob::$ran);
        Queue::assertPushed(PreparableVoidJob::class);
    }
}

class PreparableFalseJob implements Preparable, ShouldQueue
{
    use Dispatchable, Queueable;

    public function prepare(): bool
    {
        return false;
    }

    public function handle(): void
    {
    }
}

class PreparableVoidJob implements Preparable, ShouldQueue
{
    use Dispatchable, Queueable;

    public static bool $ran = false;

    public function prepare(): void
    {
        static::$ran = true;
    }

    public function handle(): void
    {
    }
}
