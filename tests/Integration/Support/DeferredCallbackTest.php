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

    public function test_a_deferred_callback_can_cancel_a_pending_callback()
    {
        $result = [];

        Route::get('/test', function () use (&$result) {
            defer(function () use (&$result) {
                $result[] = 'first';

                defer()->forget('second');
            }, 'first');

            defer(function () use (&$result) {
                $result[] = 'second';
            }, 'second');

            defer(function () use (&$result) {
                $result[] = 'third';
            }, 'third');
        });

        $this->get('/test');

        $this->assertSame(['first', 'third'], $result);
    }

    public function test_callbacks_added_after_forgetting_the_running_callback_are_invoked()
    {
        $result = [];

        Route::get('/test', function () use (&$result) {
            defer(function () use (&$result) {
                $result[] = 'first';

                defer()->forget('first');

                defer(function () use (&$result) {
                    $result[] = 'third';
                }, 'third');
            }, 'first');

            defer(function () use (&$result) {
                $result[] = 'second';
            }, 'second');
        });

        $this->get('/test');

        $this->assertSame(['first', 'second', 'third'], $result);
    }

    public function test_counting_pending_callbacks_does_not_repeat_a_running_callback()
    {
        $result = [];

        Route::get('/test', function () use (&$result) {
            defer(function () use (&$result) {
                $result[] = 'first';
            });

            defer(function () use (&$result) {
                $result[] = 'second';

                count(defer());
            });

            defer(function () use (&$result) {
                $result[] = 'third';
            });
        });

        $this->get('/test');

        $this->assertSame(['first', 'second', 'third'], $result);
    }

    public function test_cancellation_preserves_callback_conditions_on_failed_requests()
    {
        $result = [];

        Route::get('/test', function () use (&$result) {
            defer(function () use (&$result) {
                $result[] = 'first';

                defer()->forget('second');
            }, 'first')->always();

            defer(function () use (&$result) {
                $result[] = 'second';
            }, 'second')->always();

            defer(function () use (&$result) {
                $result[] = 'third';
            });

            defer(function () use (&$result) {
                $result[] = 'fourth';
            })->always();

            return response('', 500);
        });

        $this->get('/test')->assertStatus(500);

        $this->assertSame(['first', 'fourth'], $result);
    }
}
