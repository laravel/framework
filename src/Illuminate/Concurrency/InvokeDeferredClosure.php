<?php

namespace Illuminate\Concurrency;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Queue\Jobs\SyncJob;
use Laravel\SerializableClosure\SerializableClosure;
use Throwable;

/**
 * A queued closure that reports a failure on a sync connection instead of
 * rethrowing it, so a failover connection does not treat the failure as the
 * connection being down and run the task again.
 */
class InvokeDeferredClosure extends CallQueuedClosure
{
    /**
     * Create a new job instance.
     *
     * @param  \Closure  $job
     * @return static
     */
    public static function create(Closure $job)
    {
        return new static(new SerializableClosure($job));
    }

    /**
     * Execute the job.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function handle(Container $container)
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
