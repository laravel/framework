<?php

namespace Illuminate\Concurrency;

use Illuminate\Contracts\Container\Container;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Queue\Jobs\SyncJob;
use Throwable;

/**
 * The job QueueDriver::defer() dispatches.
 *
 * It is a CallQueuedClosure, so everything a deferred closure could observe
 * about the job it used to receive still holds: the type it may be hinted
 * on, the batch API, failure callbacks, the worker deciding retries, and a
 * closure whose models are gone being discarded. It differs in one place: on
 * a synchronous queue link a rethrown failure is not a recorded failure, it
 * is what makes a failover queue treat the link as dead and run the task
 * again on the next one, so there it reports the failure and returns.
 */
class InvokeDeferredClosure extends CallQueuedClosure
{
    /**
     * Execute the job.
     */
    public function handle(Container $container): void
    {
        try {
            parent::handle($container);
        } catch (Throwable $e) {
            if ($this->job instanceof SyncJob) {
                report($e);

                return;
            }

            throw $e;
        }
    }
}
