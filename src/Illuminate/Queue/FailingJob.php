<?php

namespace Illuminate\Queue;

use Illuminate\Container\Container;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Contracts\Events\Dispatcher;

class FailingJob
{
    /**
     * Delete the job, call the "failed" method, and raise the failed job event.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Queue\Jobs\Job  $job
     * @param  \Exception $e
     * @return void
     */
    public static function handle($connectionName, $job, $e = null)
    {
        $job->markAsFailed();

        if ($job->isDeleted()) {
            return;
        }

        try {
            // If the job has failed, we will delete it, call the "failed" method and then call
            // an event indicating the job has failed so it can be logged if needed. This is
            // to allow every developer to better keep monitor of their failed queue jobs.
            $job->delete();

            $job->failed($e);
        } finally {
            static::events()->fire(new JobFailed(
                $connectionName, $job, $e ?: new ManuallyFailedException
            ));
        }
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    protected static function events()
    {
        return Container::getInstance()->make(Dispatcher::class);
    }
}
