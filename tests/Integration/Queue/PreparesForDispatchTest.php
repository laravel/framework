<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Support\Facades\Queue;
use Illuminate\Tests\App\Jobs\PreparesForDispatchFalseJob;
use Illuminate\Tests\App\Jobs\PreparesForDispatchVoidJob;
use Orchestra\Testbench\TestCase;

class PreparesForDispatchTest extends TestCase
{
    public function test_does_not_dispatch_when_prepare_returns_false()
    {
        Queue::fake();

        PreparesForDispatchFalseJob::dispatch();

        Queue::assertNotPushed(PreparesForDispatchFalseJob::class);
    }

    public function test_dispatches_when_prepare_returns_void()
    {
        Queue::fake();

        PreparesForDispatchVoidJob::$ran = false;

        PreparesForDispatchVoidJob::dispatch();

        $this->assertTrue(PreparesForDispatchVoidJob::$ran);
        Queue::assertPushed(PreparesForDispatchVoidJob::class);
    }
}
