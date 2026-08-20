<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks;
use Illuminate\Support\Facades\Route;
use Illuminate\Tests\App\Jobs\TestSyncJob;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

class DeferredCallbackTest extends TestCase
{
    #[WithConfig('queue.default', 'sync')]
    public function test_deferred_callback_is_not_discarded_by_sync_job()
    {
        $executed = false;

        Route::get('/test', function () use (&$executed) {
            defer(function () use (&$executed) {
                $executed = true;
            });

            dispatch(new TestSyncJob);
        })->middleware(InvokeDeferredCallbacks::class);

        $this->get('/test');

        $this->assertTrue($executed);
    }
}
