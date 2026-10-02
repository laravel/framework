<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks;
use Illuminate\Support\Facades\Route;
use Illuminate\Tests\Queue\Fixtures\TestSyncJob;
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

    public function test_callbacks_deferred_within_a_deferred_callback_are_invoked()
    {
        $result = [];

        Route::get('/test', function () use (&$result) {
            defer(function () use (&$result) {
                $result[] = 'first';

                defer(function () use (&$result) {
                    $result[] = 'second';
                });
            });
        });

        $this->get('/test');

        $this->assertSame(['first', 'second'], $result);
    }
}
